<?php

namespace App\Models;

use App\Notifications\CustomVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'street',
        'barangay',
        'city',
        'zip_code',
        'username',
        'role',
        'fingerprint_template_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_code',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'email_verified_at' => 'datetime',
        'email_verification_code_expires_at' => 'datetime',
        'fingerprint_template_id' => 'integer',
        'password' => 'hashed',
    ];

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->first_name,
            $this->last_name,
        ])));
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->full_name !== '') {
            return $this->full_name;
        }

        if (filled($this->username)) {
            return (string) $this->username;
        }

        return (string) $this->email;
    }

    public function getInitialsAttribute(): string
    {
        if ($this->full_name !== '') {
            $firstInitial = substr((string) $this->first_name, 0, 1);
            $lastInitial = substr((string) $this->last_name, 0, 1);

            return strtoupper($firstInitial.($lastInitial ?: $firstInitial));
        }

        $fallback = $this->username ?: $this->email ?: 'U';

        return strtoupper(substr((string) $fallback, 0, 2));
    }

    /**
     * Get formatted barangay name.
     */
    public function getBarangayNameAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->barangay));
    }

    /**
     * Get the bookings for the user.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Bookings assigned to the user as staff.
     */
    public function assignedBookings()
    {
        return $this->hasMany(Booking::class, 'staff_id');
    }

    public function enrollmentRequests()
    {
        return $this->hasMany(DeviceEnrollmentRequest::class);
    }

    /**
     * Get notifications sent to this user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    /**
     * Get unread notifications for this user.
     */
    public function unreadNotifications()
    {
        return $this->notifications()->unread();
    }

    public function bookingMessages()
    {
        return $this->hasMany(BookingMessage::class, 'sender_id');
    }

    public function sendEmailVerificationNotification(): void
    {
        $expiresInMinutes = (int) config('auth.verification.expire', 15);
        $code = $this->issueEmailVerificationCode($expiresInMinutes);

        $this->notify(new CustomVerifyEmail($code, $expiresInMinutes));
    }

    public function issueEmailVerificationCode(int $expiresInMinutes = 15): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_verification_code' => Hash::make($code),
            'email_verification_code_expires_at' => now()->addMinutes($expiresInMinutes),
        ])->save();

        return $code;
    }

    public function hasMatchingEmailVerificationCode(string $code): bool
    {
        if ($this->email_verification_code === null || $this->emailVerificationCodeExpired()) {
            return false;
        }

        return Hash::check($code, $this->email_verification_code);
    }

    public function emailVerificationCodeExpired(): bool
    {
        return $this->email_verification_code_expires_at === null
            || $this->email_verification_code_expires_at->isPast();
    }

    public function clearEmailVerificationCode(): void
    {
        $this->forceFill([
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ])->save();
    }
}
