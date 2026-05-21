<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FactionType;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $score = fake()->numberBetween(-100, 100);
        $faction = FactionType::fromScore($score);

        return [
            'email'               => fake()->unique()->safeEmail(),
            'nickname'            => fake()->unique()->userName(),
            'password'            => static::$password ??= Hash::make('password'),
            'political_type'      => $faction,
            'test_score'          => $score,
            'status'              => UserStatus::Active,
            'is_admin'            => false,
            'manner_score'        => fake()->numberBetween(50, 100),
            'test_completed_at'   => now(),
            'email_verified_at'   => now(),
            'remember_token'      => Str::random(10),
        ];
    }

    /**
     * 이메일 미인증 상태.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * 보수 진영 사용자.
     */
    public function conservative(): static
    {
        return $this->state(fn (array $attributes) => [
            'political_type' => FactionType::Conservative,
            'test_score'     => fake()->numberBetween(25, 100),
        ]);
    }

    /**
     * 중도 진영 사용자.
     */
    public function moderate(): static
    {
        return $this->state(fn (array $attributes) => [
            'political_type' => FactionType::Moderate,
            'test_score'     => fake()->numberBetween(-24, 24),
        ]);
    }

    /**
     * 진보 진영 사용자.
     */
    public function progressive(): static
    {
        return $this->state(fn (array $attributes) => [
            'political_type' => FactionType::Progressive,
            'test_score'     => fake()->numberBetween(-100, -25),
        ]);
    }

    /**
     * 관리자 계정.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_admin' => true,
            'status'   => UserStatus::Active,
        ]);
    }

    /**
     * 정지된 계정.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'       => UserStatus::Suspended,
            'suspended_until' => now()->addDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
