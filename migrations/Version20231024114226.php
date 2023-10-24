<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231024114226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create basket table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE basket_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql(<<<SQL
            CREATE TABLE basket (
                id INT NOT NULL, 
                owner_id INT DEFAULT NULL, 
                identifier UUID NOT NULL, 
                status VARCHAR(20) NOT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                guest_token TEXT DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        SQL);

        $this->addSql('CREATE UNIQUE INDEX UNIQ_2246507B772E836A ON basket (identifier)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2246507B7E3C61F9 ON basket (owner_id)');
        $this->addSql('COMMENT ON COLUMN basket.identifier IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN basket.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN basket.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql(<<<SQL
            ALTER TABLE basket ADD CONSTRAINT FK_2246507B7E3C61F9 FOREIGN KEY (owner_id) 
                REFERENCES public."user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE 
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE basket_id_seq CASCADE');
        $this->addSql('ALTER TABLE basket DROP CONSTRAINT FK_2246507B7E3C61F9');
        $this->addSql('DROP TABLE basket');
    }
}
