<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Membership>
 */
class MembershipFactory extends Factory
{
    public function definition(): array
    {
        $ended = fake()->boolean(30);

        return [
            'user_id' => \App\Models\User::factory(),
            'ended_at' => $ended ? fake()->dateTimeBetween('-1 years', 'now') : null,
            'ends_at' => $ended ? null : fake()->dateTimeBetween('now', '+1 month'),
        ];
    }
}
