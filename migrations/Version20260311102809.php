<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311102809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE intranet_page (id INT AUTO_INCREMENT NOT NULL, route_name VARCHAR(255) NOT NULL, label VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_B37F672BF3667F83 (route_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE intranet_page_access_group (intranet_page_id INT NOT NULL, access_group_id INT NOT NULL, INDEX IDX_DE68BDE1B85FFD4F (intranet_page_id), INDEX IDX_DE68BDE193411876 (access_group_id), PRIMARY KEY(intranet_page_id, access_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE intranet_page_access_group ADD CONSTRAINT FK_DE68BDE1B85FFD4F FOREIGN KEY (intranet_page_id) REFERENCES intranet_page (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_page_access_group ADD CONSTRAINT FK_DE68BDE193411876 FOREIGN KEY (access_group_id) REFERENCES access_group (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE intranet_page_access_group DROP FOREIGN KEY FK_DE68BDE1B85FFD4F');
        $this->addSql('ALTER TABLE intranet_page_access_group DROP FOREIGN KEY FK_DE68BDE193411876');
        $this->addSql('DROP TABLE intranet_page');
        $this->addSql('DROP TABLE intranet_page_access_group');
    }
}
