<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('feedback')]
#[Fillable(['user_id', 'score', 'comment', 'page'])]
class Feedback extends Model
{
    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return ['score' => 'integer'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
