<?php

namespace App\Security;

use App\Doctrine\SubtripOptionsType;
use App\Entity\Member;
use App\Entity\Trip;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TripVoter extends Voter
{
    public const TRIP_VIEW = 'TRIP_VIEW';
    public const TRIP_EDIT = 'TRIP_EDIT';

    protected function supports(string $attribute, $subject): bool
    {
        if (!\in_array($attribute, [self::TRIP_VIEW, self::TRIP_EDIT], true)) {
            return false;
        }

        if (!$subject instanceof Trip) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var Member $member */
        $member = $token->getUser();

        if (!$member instanceof Member) {
            // the member must be logged in; if not, deny access
            return false;
        }

        /** @var Trip */
        $trip = $subject;
        if (null !== $trip->getDeleted()) {
            // A deleted trip can't be viewed or edited.
            return false;
        }

        switch ($attribute) {
            case self::TRIP_VIEW:
                // A trip that hasn't expired can be viewed by everyone as long as not all legs are private
                $view = !$trip->isExpired() || $this->canEdit($trip, $member);
                foreach ($trip->getSubtrips() as $leg) {
                    $view = $view || !in_array(SubtripOptionsType::PRIVATE, $leg->getOptions());
                }
                return $view;
            case self::TRIP_EDIT:
                return $this->canEdit($trip, $member);
        }

        throw new LogicException('This code should not be reached!');
    }

    private function canEdit(Trip $trip, Member $member): bool
    {
        return $member === $trip->getCreator();
    }
}
