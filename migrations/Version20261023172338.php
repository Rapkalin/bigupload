<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261023172338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index items.show_id (download pages) and items.expiration_date (purge command)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_items_show_id ON items (show_id)');
        $this->addSql('CREATE INDEX idx_items_expiration_date ON items (expiration_date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_items_show_id ON items');
        $this->addSql('DROP INDEX idx_items_expiration_date ON items');
    }
}
