<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

use App\Entity\Room;
use App\Entity\User;
use App\Entity\Stream;
use App\Entity\Message;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $room1 = new Room();
        $room1->setName("Appart");
        $room1->setDescription("Organisons-nous !");
        
        $room2 = new Room();
        $room2->setName("Travail");
        $room2->setDescription("Projet concorde");

        $room3 = new Room();
        $room3->setName("Sport");
        $room3->setDescription("Entrainements et vidéos");
        
        $user1 = new User();
        $user1->setPseudo("Max");
        $user1->setIsAdmin(true);
        
        $user2 = new User();
        $user2->setPseudo("Lika");
        $user2->setIsAdmin(true);
        
        $room1->addUser($user1);
        $room1->addUser($user2);

        $stream1 = new Stream();
        $stream1->setName("Général");
        $stream1->setColorBg("D9D9D9");
        $stream1->setColorTxt("000000");
        $stream1->setRoom($room1);

        $stream2 = new Stream();
        $stream2->setName("Budget");
        $stream2->setColorBg("DA7C7C");
        $stream2->setColorTxt("000000");
        $stream2->setRoom($room1);
        
        $message1 = new Message();
        $message1->setText("Je ne sais pas quoi faire ce soir, mais je pense que j'aimerais sortir");
        $message1->setUser($user2);
        $message1->setRoom($room1);
        $message1->setStream($stream1);

        $message2 = new Message();
        $message2->setText("Et moi, je veux manger. Resto ?");
        $message2->setUser($user1);
        $message2->setRoom($room1);
        $message2->setStream($stream1);

        $message3 = new Message();
        $message3->setText("Il nous reste 40€ de budget sortie pour ce mois-ci.");
        $message3->setUser($user2);
        $message3->setRoom($room1);
        $message3->setStream($stream2);

        $message4 = new Message();
        $message4->setText("Ok, ça ira ! Ah, je suis allé au garagiste ce matin, j'en ai eu pour 200€ de réparation.");
        $message4->setUser($user1);
        $message4->setRoom($room1);
        $message4->setStream($stream1);

        for($i=0; $i<10; $i++) {
            $msg = new Message();
            $msg->setText("Message " . $i );
            if ($i%2 == 1) 
                {$msg->setUser($user1);} 
                else 
                {$msg->setUser($user2);};
            $msg->setRoom($room1);
            $msg->setStream($stream1);

            $manager->persist($msg);
        }
        
        $manager->persist($room1);
        $manager->persist($room2);
        $manager->persist($room3);

        $manager->persist($user1);
        $manager->persist($user2);

        $manager->persist($stream1);
        $manager->persist($stream2);

        $manager->persist($message1);
        $manager->persist($message2);
        $manager->persist($message3);
        $manager->persist($message4);

        $manager->flush();
    }
}
