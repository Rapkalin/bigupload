<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use DateMalformedStringException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Exception;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\Table(name: 'items')]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $download_page_url = null;

    #[ORM\Column(length: 255)]
    private ?string $download_file_url = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $expiration_date = null;

    #[ORM\Column(length: 255)]
    private ?string $size = null;

    #[ORM\Column(length: 255)]
    private ?string $extension = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\Column(length: 255)]
    private ?string $show_id = null;

    /**
     * @throws DateMalformedStringException
     * @throws Exception
     */
    public function setItem(array $data): Item {
        foreach ($data as $key => $field) {
             if (!$field) {
                throw new Exception("Missing $key field.");
             }
        }

        $item = new Item();
        $item->setTitle($data['title']);
        $item->setDownloadPageUrl($data['download_page_url']);
        $item->setDownloadFileUrl($data['download_file_url']);
        $item->setExtension($data['extension']);
        $item->setSize($data['size']);
        $item->setCreatedAt($data['created_at']);
        $item->setExpirationDate($data['expiration_date']);
        $item->setShowId($data['show_id']);

        return $item;
    }

    public function formatData(): array
    {
        return [
            'title' => $this->getTitle(),
            'expiration_date' => bgpld_strftime('%d %B %Y' , strtotime($this->getExpirationDate()->format('j F Y')), 'fr_FR'),
            'expiration_time' => $this->getExpirationTime($this->getExpirationDate()),
            'size' => formatBytes($this->getSize()),
            'extension' => $this->getExtension(),
            'download_file_url' => $this->getDownloadFileUrl(),
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDownloadPageUrl(): ?string
    {
        return $this->download_page_url;
    }

    public function setDownloadPageUrl(string $download_page_url): static
    {
        $this->download_page_url = $download_page_url;
        return $this;
    }

    public function getDownloadFileUrl(): ?string
    {
        return $this->download_file_url;
    }

    public function setDownloadFileUrl(string $download_file_url): static
    {
        $this->download_file_url = $download_file_url;
        return $this;
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->expiration_date;
    }

    public function setExpirationDate(string $expiration_date): static
    {
        $this->expiration_date = new \DateTimeImmutable($expiration_date);
        return $this;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function setSize(string $size): static
    {
        $this->size = $size;
        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): static
    {
        $this->extension = $extension;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function setCreatedAt(string $created_at): static
    {
        $this->created_at = new \DateTimeImmutable($created_at);
        return $this;
    }

    public function getShowId(): ?string
    {
        return $this->show_id;
    }

    public function setShowId(string $show_id): static
    {
        $this->show_id = $show_id;
        return $this;
    }

    public function getExpirationTime(\DateTimeImmutable $expiration_date): string
    {
        $now = new \DateTimeImmutable();
        $interval = $now->diff($expiration_date);
        $pluralOrSing = $interval->d > 1 ? 'jours' : 'jour';
        return $interval->format('%d ' . $pluralOrSing);
    }
}
