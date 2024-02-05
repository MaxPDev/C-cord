<?php

namespace App\Entity;

use App\Repository\RoomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoomRepository::class)]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'room')]
    private Collection $user;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Message::class, orphanRemoval: true)]
    private Collection $messages;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Stream::class, orphanRemoval: true)]
    private Collection $streams;

    public function __construct()
    {
        $this->user = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->streams = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUser(): Collection
    {
        return $this->user;
    }

    public function addUser(User $user): static
    {
        if (!$this->user->contains($user)) {
            $this->user->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->user->removeElement($user);

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
            $message->setRoom($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getRoom() === $this) {
                $message->setRoom(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Stream>
     */
    public function getStreams(): Collection
    {
        return $this->streams;
    }

    public function addStream(Stream $stream): static
    {
        if (!$this->streams->contains($stream)) {
            $this->streams->add($stream);
            $stream->setRoom($this);
        }

        return $this;
    }

    public function removeStream(Stream $stream): static
    {
        if ($this->streams->removeElement($stream)) {
            // set the owning side to null (unless already changed)
            if ($stream->getRoom() === $this) {
                $stream->setRoom(null);
            }
        }

        return $this;
    }
}
