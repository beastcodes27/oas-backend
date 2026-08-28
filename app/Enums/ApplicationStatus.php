<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Reviewing = 'reviewing';
    case Approved = 'approved';
    case Declined = 'declined';
    case Selected = 'selected';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Verified => 'Verified',
            self::Reviewing => 'Reviewing',
            self::Approved => 'Approved',
            self::Declined => 'Declined',
            self::Selected => 'Selected',
            self::Rejected => 'Rejected',
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Selected || $this === self::Rejected;
    }

    public function isDraftDecision(): bool
    {
        return $this === self::Approved || $this === self::Declined;
    }
}
