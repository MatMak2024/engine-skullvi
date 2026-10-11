<?php

namespace App\Models;

use App\Enums\Role as RoleName;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property string $id
 * @property string $first_name
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $country
 * @property Carbon|null $phone_verified_at
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property Carbon|null $deleted_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Candidate|null $candidate
 * @property-read Collection<int, Role> $roles
 * @property-read Collection<int, ReviewNote> $reviewNotes
 */
#[Fillable(['first_name', 'name', 'email', 'phone', 'address', 'city', 'state', 'zip', 'country', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @var list<string> */
    protected $appends = ['display_name'];

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, PasskeyAuthenticatable, SoftDeletes, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'candidate_removed_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<Candidate, $this>
     */
    public function candidate(): HasOne
    {
        return $this->hasOne(Candidate::class);
    }

    /**
     * @return HasMany<SavedOpening, $this>
     */
    public function savedOpenings(): HasMany
    {
        return $this->hasMany(SavedOpening::class);
    }

    /**
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * @return HasMany<ReviewNote, $this>
     */
    public function reviewNotes(): HasMany
    {
        return $this->hasMany(ReviewNote::class);
    }

    /**
     * L'inscription envoie déjà le lien pour choisir le mot de passe.
     * Le renvoi ne part que pour un compte qui a déjà un mot de passe.
     */
    public function sendEmailVerificationNotification(): void
    {
        if ($this->password === null) {
            return;
        }

        $this->notify(new VerifyEmail);
    }

    public function displayName(): string
    {
        $firstName = trim((string) $this->first_name);
        $name = trim((string) $this->name);

        if ($firstName === '' || $firstName === $name) {
            return $name;
        }

        return trim($firstName.' '.$name);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->displayName();
    }

    /**
     * @return array{first_name: string, name: string}
     */
    public static function splitName(string $full): array
    {
        $parts = preg_split('/\s+/u', trim($full), 2) ?: [];
        $firstName = ($parts[0] ?? '') !== '' ? $parts[0] : 'Compte';

        return [
            'first_name' => $firstName,
            'name' => $parts[1] ?? $firstName,
        ];
    }

    public function isCandidate(): bool
    {
        return $this->candidate()->exists();
    }

    public function isPermanentlyRemovedAsCandidate(): bool
    {
        return $this->candidate_removed_at !== null;
    }

    public function hasRole(RoleName|string $role): bool
    {
        $slug = $role instanceof RoleName ? $role->value : $role;

        return $this->roles()->where('slug', $slug)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()->whereHas(
            'permissions',
            fn ($query) => $query->where('name', $permission),
        )->exists();
    }
}
