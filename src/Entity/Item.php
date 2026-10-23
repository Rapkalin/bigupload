<?php

namespace App\Entity;

use App\Constant\UploadConfig;
use App\Repository\ItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\Table(name: 'items')]
#[ORM\Index(name: 'idx_items_show_id', columns: ['show_id'])]
#[ORM\Index(name: 'idx_items_expiration_date', columns: ['expiration_date'])]
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

    #[ORM\Column(length: 255)]
    private ?string $zip_name = null;

    public static function create(
        string $title,
        string $showId,
        string $zipName,
        int $size,
        string $downloadPageUrl,
        string $downloadFileUrl,
    ): self {
        $createdAt = new \DateTimeImmutable();

        $item = new self();
        $item->title = $title;
        $item->show_id = $showId;
        $item->zip_name = $zipName;
        $item->size = (string) $size;
        $item->extension = pathinfo($title, PATHINFO_EXTENSION);
        $item->download_page_url = $downloadPageUrl;
        $item->download_file_url = $downloadFileUrl;
        $item->created_at = $createdAt;
        $item->expiration_date = $createdAt->modify('+' . UploadConfig::EXPIRATION_DAYS . ' days');

        return $item;
    }

    public function formatData(): array
    {
        return [
            'title' => $this->title,
            'show_id' => $this->show_id,
            'expiration_time' => $this->getExpirationTime(),
            'size' => formatBytes((int) $this->size),
            'extension' => $this->extension,
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

    public function getDownloadPageUrl(): ?string
    {
        return $this->download_page_url;
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->expiration_date;
    }

    public function getSize(): ?string
    {
        return $this->size;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getShowId(): ?string
    {
        return $this->show_id;
    }

    public function getZipName(): ?string
    {
        return $this->zip_name;
    }

    private function getExpirationTime(): string
    {
        $days = (new \DateTimeImmutable())->diff($this->expiration_date)->days;

        return $days . ($days > 1 ? ' days' : ' day');
    }
}
