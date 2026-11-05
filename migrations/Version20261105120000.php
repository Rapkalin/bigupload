<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261105120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop messenger_messages (symfony/messenger removed, never used) and the DBAL 3 type comments of items';
    }

    public function up(Schema $schema): void
    {
        // Never drop messages silently: the deployment stops if the table was used after all
        $this->abortIf(
            0 !== (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages'),
            'messenger_messages is not empty: check its content before dropping it.'
        );

        $this->addSql('DROP TABLE messenger_messages');
        // DBAL 4 no longer reads the column comments (DC2Type): schema:validate expects none
        $this->addSql('ALTER TABLE items CHANGE expiration_date expiration_date DATETIME NOT NULL, CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE items CHANGE expiration_date expiration_date DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }
}
