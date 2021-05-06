<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'repo_link' => $this->repo_link,
            'deadline' => $this->deadline,
            'max_member_count' => $this->max_member_count,
            'stacks' => StackResource::collection($this->stacks()->get())->withoutWrapping(),
            'status' => StatusResource::collection($this->status()->get())->withoutWrapping(),
            'type' => TypeResource::collection($this->type()->get())->withoutWrapping(),
            'users' => [
                'authors' => (new UserCollection(
                    $this->users()->wherePivot('role', 'AUTHOR')->get()
                ))->withoutWrapping(),
                'maintainers' => (new UserCollection(
                    $this->users()->wherePivot('role', 'MAINTAINER')->get()
                ))->withoutWrapping(),
                'developers' => (new UserCollection(
                    $this->users()->wherePivot('role', 'DEVELOPERS')->get()
                ))->withoutWrapping(),
            ],
            'feedbacks' => FeedbackResource::collection(
                $this->feedbacks()->where('sender_id', auth()->user()->id)
                                ->orWhere('receiver_id', auth()->user()->id)->get()
            )->withoutWrapping()
        ];
    }

    /**
     * Get any additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'message' => 'Success!'
        ];
    }
}
