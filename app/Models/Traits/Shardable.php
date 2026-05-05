<?php

namespace App\Models\Traits;

trait Shardable
{
    public function getShardConnection(): string
    {
        $shardId = $this->id % 4; // Routes traffic to 4 separate shards
        return "mysql_shard_{$shardId}";
    }
}