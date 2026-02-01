<?php

namespace App\Events;

use App\Models\DesignAnnotation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnotationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public DesignAnnotation $annotation
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('design.' . $this->annotation->design_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'annotation.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'annotation' => [
                'id' => $this->annotation->id,
                'type' => $this->annotation->type,
                'data' => $this->annotation->data,
                'color' => $this->annotation->color,
                'stroke_width' => $this->annotation->stroke_width,
                'label' => $this->annotation->label,
                'user' => [
                    'id' => $this->annotation->user_id,
                    'name' => $this->annotation->user->name ?? 'Unknown',
                ],
                'created_at' => $this->annotation->created_at->toISOString(),
            ],
        ];
    }
}
