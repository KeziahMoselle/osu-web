<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Transformers;

use App\Models\ContestEntry;
use App\Models\DeletedUser;

class ContestEntryTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'artMeta',
        'results',
        'user',
    ];

    public function transform(ContestEntry $entry)
    {
        $return = [
            'id' => $entry->id,
            'title' => $entry->contest->unmasked ? $entry->name : $entry->masked_name,
            'preview' => $entry->entry_url,
        ];

        if ($entry->contest->hasThumbnails()) {
            $return['thumbnail'] = mini_asset($entry->thumbnail());
        }

        return $return;
    }

    public function includeResults(ContestEntry $entry)
    {
        return $this->primitive([
            'actual_name' => $entry->name,
            'votes' => (int) $entry->votes_count,
        ]);
    }

    public function includeUser(ContestEntry $entry)
    {
        $user = $entry->user ?? (new DeletedUser());

        return $this->primitive([
            'id' => $user->getKey(),
            'username' => $user->username,
        ]);
    }

    public function includeArtMeta(ContestEntry $entry)
    {
        if (!$entry->contest->hasThumbnails() || !presence($entry->entry_url)) {
            return $this->primitive([]);
        }

        // suffix urls when contests are made live to ensure image dimensions are forcibly rechecked
        if ($entry->contest->visible) {
            $urlSuffix = str_contains($entry->thumbnail(), '?') ? '&live' : '?live';
        }

        $size = fast_imagesize($entry->thumbnail().($urlSuffix ?? ''));

        return $this->primitive([
            'width' => $size[0] ?? 0,
            'height' => $size[1] ?? 0,
        ]);
    }
}
