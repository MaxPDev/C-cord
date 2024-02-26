<?php

namespace App\Entity;

use App\Repository\StreamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StreamRepository::class)]
class Stream
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getRoom",'getMessage', 'getStreams','getStream'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getRoom",'getMessage','getStreams','getStream'])]
    #[Assert\NotBlank(message: "Nom du nouveau stream requis")]
    #[Assert\Length(min:1, max: 255, 
      minMessage:"Nom de la room : {{ limit }} caractères minimum",
      maxMessage:"Nom de la room : {{ limit }} caractères maximum")]
    private ?string $name = null;

    #[Groups(['getMessage','getStreams','getStream'])]
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message:'Couleur du fond requis. Format : Hexadécimal')]
    #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG,
                      message: 'La couleur doit être au format hexadécimal de 6 charactères e.g. #2F2F2F')]
    private ?string $color_bg = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getMessage','getStreams','getStream'])]
    #[Assert\NotBlank(message:'Couleur du texte requis. Format : Hexadécimal')]
    #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG,
                      message: 'La couleur doit être au format hexadécimal de 6 charactères e.g. #2F2F2F')]
    private ?string $color_txt = null;

    #[ORM\OneToMany(mappedBy: 'stream', targetEntity: Message::class, orphanRemoval: true)]
    #[Groups(['getStream'])]
    private Collection $messages;

    #[ORM\ManyToOne(inversedBy: 'streams')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['getStreams','getStream'])]
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
