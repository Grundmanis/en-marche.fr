<?php

namespace AppBundle\Committee;

use AppBundle\Address\PostAddressFactory;
use AppBundle\Entity\Committee;
use libphonenumber\PhoneNumber;
use Ramsey\Uuid\Uuid;

class CommitteeFactory
{
    private $addressFactory;

    public function __construct(PostAddressFactory $addressFactory = null)
    {
        $this->addressFactory = $addressFactory ?: new PostAddressFactory();
    }

    public function createFromArray(array $data): Committee
    {
        foreach (['name', 'description', 'created_by', 'address'] as $key) {
            if (empty($data[$key])) {
                throw new \InvalidArgumentException(sprintf('Key "%s" is missing or has an empty value.', $key));
            }
        }

        $phone = null;
        if (isset($data['phone'])) {
            $phone = $this->createPhone($data['phone']);
        }

        $uuid = isset($data['uuid'])
            ? Uuid::fromString($data['uuid'])
            : Committee::createUuid($data['name']);

        $committee = Committee::createSimple(
            $uuid,
            $data['created_by'],
            $data['name'],
            $data['description'],
            $data['address'],
            $phone,
            $data['created_at'] ?? 'now'
        );

        $committee->setSocialNetworks(
            $data['facebook_page_url'] ?? null,
            $data['twitter_nickname'] ?? null,
            $data['google_plus_page_url'] ?? null
        );

        if (isset($data['slug'])) {
            $committee->updateSlug($data['slug']);
        }

        return $committee;
    }

    /**
     * Returns a new instance of Committee from a CreateCommitteeCommand DTO.
     *
     * @param CommitteeCreationCommand $command
     *
     * @return Committee
     */
    public function createFromCommitteeCreationCommand(CommitteeCreationCommand $command): Committee
    {
        $committee = Committee::createForAdherent(
            $command->getAdherent(),
            $command->name,
            $command->description,
            $this->addressFactory->createFromAddress($command->getAddress()),
            $command->getPhone()
        );

        if ($command->facebookPageUrl) {
            $committee->setFacebookPageUrl($command->facebookPageUrl);
        }

        if ($command->twitterNickname) {
            $committee->setTwitterNickname($command->twitterNickname);
        }

        if ($command->googlePlusPageUrl) {
            $committee->setGooglePlusPageUrl($command->googlePlusPageUrl);
        }

        return $committee;
    }

    /**
     * Returns a PhoneNumber object.
     *
     * The format must be something like "33 0102030405"
     *
     * @param string $phoneNumber
     *
     * @return PhoneNumber
     */
    private function createPhone($phoneNumber): PhoneNumber
    {
        list($country, $number) = explode(' ', $phoneNumber);

        $phone = new PhoneNumber();
        $phone->setCountryCode($country);
        $phone->setNationalNumber($number);

        return $phone;
    }
}
