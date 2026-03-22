<?php
namespace App\DataFixtures;

use App\Entity\Admin;
use App\Entity\Event;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create admin
        $admin = new Admin();
        $admin->setUsername('admin');
        $admin->setPassword(
            $this->hasher->hashPassword($admin, 'admin123')
        );
        $manager->persist($admin);

        // Create test events
        $events = [
            ['Tech Conference 2026', 'A major tech conference covering AI, Web, and Cloud.', '+7 days', 'Sousse, Tunisia', 200],
            ['Music Festival', 'Live music performances from local and international artists.', '+14 days', 'Tunis, Tunisia', 500],
            ['Startup Pitch Night', 'Startups pitch their ideas to investors.', '+3 days', 'Sfax, Tunisia', 100],
            ['Web Dev Workshop', 'Hands-on Symfony and React workshop for developers.', '+10 days', 'Monastir, Tunisia', 50],
        ];

        foreach ($events as [$title, $desc, $offset, $location, $seats]) {
            $event = new Event();
            $event->setTitle($title);
            $event->setDescription($desc);
            $event->setDate(new \DateTime($offset));
            $event->setLocation($location);
            $event->setSeats($seats);
            $manager->persist($event);
        }

        $manager->flush();
    }
}
