<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getRoom",'getMessage','getStream'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['getMessage','getStream'])]
    #[Assert\NotBlank(message: "Écrivez un message !")]
    private ?string $text = null;

    #[ORM\Column]
    #[Groups(['getMessage'])] //? DateImmutableNormalizer use ?
    //? Demande une string si ces Assert sont actifs
    // #[Assert\NotNull(message:'Une date doit être fournie pour ce message')]
    // #[Assert\DateTime(message:'Une date doit être fourni pour ce message')]
    private ?\DateTimeImmutable $created_at = null;

    //! $updated_at !!

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['getMessage'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['getMessage'])]
    private ?Room $room = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['getMessage'])]
    private ?Stream $stream = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function getStream(): ?Stream
    {
        return $this->stream;
    }

    public function setStream(?Stream $stream): static
    {
        $this->stream = $stream;

        return $this;
    }
}
