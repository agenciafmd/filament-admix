<?php

declare(strict_types=1);

namespace Agenciafmd\Admix\Resources\Users\Pages;

use Agenciafmd\Admix\Resources\Concerns\RedirectBack;
use Agenciafmd\Admix\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateUser extends CreateRecord
{
    use RedirectBack;

    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        //        TODO: https://filamentmastery.com/articles/email-verification-in-filament-userresource-filters-and-actions
        //        $user = $this->record;
        //        $this->sendEmailVerificationNotification($user);
    }

    //    protected function sendEmailVerificationNotification(Model $user): void
    //    {
    //        if (! $user instanceof MustVerifyEmail) {
    //            return;
    //        }
    //
    //        if ($user->hasVerifiedEmail()) {
    //            return;
    //        }
    //
    //        if (! method_exists($user, 'notify')) {
    //            $userClass = $user::class;
    //
    //            throw new LogicException("Model [{$userClass}] does not have a [notify()] method.");
    //        }
    //
    //        $notification = app(VerifyEmail::class);
    //        $notification->url = Filament::getVerifyEmailUrl($user);
    //
    //        $user->notify($notification);
    //    }
}
