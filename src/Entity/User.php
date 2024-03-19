<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

//* Serializer-pack annotations
// use Symfony\Component\Serializer\Annotation\Groups;
//* JMS Serializer annotations
use JMS\Serializer\Annotation\Groups;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getOneRoom","getAllUsers",'getOneUser','getOneMessage'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getOneRoom", "getAllUsers",'getOneUser','getOneMessage'])]
    #[Assert\NotBlank(message: "Pseudo requis")]
    #[Assert\Length(min:3, max: 255, 
      minMessage:"Nom de la room : {{ limit }} caractères minimum",
      maxMessage:"Nom de la room : {{ limit }} caractères maximum")]
    private ?string $pseudo = null;

    #[ORM\Column]
    #[Groups(["getAllUsers",'getOneUser'])]
    #//? Assert ??
    private ?bool $isAdmin = null;

    //? Later : fileType ? Image ?
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(["getAllUsers",'getOneUser'])]
    private ?string $avatar = null;

    #[ORM\ManyToMany(targetEntity: Room::class, mappedBy: 'user')]
    #[Groups(["getAllUsers",])]
    private Collection $room;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Message::class, orphanRemoval: true)]
    private Collection $messages;

    public function __construct()
    {
        $this->room = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function isIsAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): static
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return Collection<int, Room>
     */
    public function getRoom(): Collection
    {
        return $this->room;
    }

    public function addRoom(Room $room): static
    {
        if (!$this->room->contains($room)) {
            $this->room->add($room);
            $room->addUser($this);
        }

        return $this;
    }

    public function removeRoom(Room $room): static
    {
        if ($this->room->removeElement($room)) {
            $room->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setUser($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getUser() === $this) {
                $message->setUser(null);
            }
        }

        return $this;
    }
}
