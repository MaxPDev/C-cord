<?php

namespace App\Entity;

use App\Repository\StreamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StreamRepository::class)]
class Stream
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $color_bg = null;

    #[ORM\Column(length: 255)]
    private ?string $color_txt = null;

    #[ORM\OneToMany(mappedBy: 'stream', targetEntity: Message::class, orphanRemoval: true)]
    private Collection $messages;

    #[ORM\ManyToOne(inversedBy: 'streams')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
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

    public function getColorBg(): ?string
    {
        return $this->color_bg;
    }

    public function setColorBg(string $color_bg): static
    {
        $this->color_bg = $color_bg;

        return $this;
    }

    public function getColorTxt(): ?string
    {
        return $this->color_txt;
    }

    public function setColorTxt(string $color_txt): static
    {
        $this->color_txt = $color_txt;

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
            $message->setStream($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getStream() === $this) {
                $message->setStream(null);
            }
        }

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
}
