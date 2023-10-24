<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231024125337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create basket_item table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(
            'CREATE SEQUENCE public.basket_item_id_seq INCREMENT BY 1 MINVALUE 1 START 1'
        );
        $this->addSql(<<<SQL
            CREATE TABLE public.basket_item (
                basket_id INT NOT NULL, 
                product_id INT NOT NULL,
                identifier UUID NOT NULL,
                quantity INT NOT NULL, 
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                PRIMARY KEY(basket_id, product_id)
            )
        SQL);
        $this->addSql(
            'CREATE UNIQUE INDEX UNIQ_58E27EC1772E836A ON public.basket_item (identifier)'
        );
        $this->addSql('CREATE INDEX IDX_58E27EC11BE1FB52 ON public.basket_item (basket_id)');
        $this->addSql('CREATE INDEX IDX_58E27EC14584665A ON public.basket_item (product_id)');
        $this->addSql(
            'COMMENT ON COLUMN public.basket_item.created_at IS \'(DC2Type:datetime_immutable)\''
        );
        $this->addSql(
            'COMMENT ON COLUMN public.basket_item.updated_at IS \'(DC2Type:datetime_immutable)\''
        );
        $this->addSql(<<<SQL
            ALTER TABLE public.basket_item ADD CONSTRAINT FK_58E27EC11BE1FB52 
                FOREIGN KEY (basket_id) REFERENCES public.basket (id) 
                    NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<SQL
            ALTER TABLE public.basket_item ADD CONSTRAINT FK_58E27EC14584665A 
                FOREIGN KEY (product_id) REFERENCES public.product (id) 
                    NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE public.basket_item_id_seq CASCADE');
        $this->addSql('DROP TABLE public.basket_item');
    }
}
