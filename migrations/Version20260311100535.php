<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311100535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_access_group (user_id INT NOT NULL, access_group_id INT NOT NULL, INDEX IDX_4994494A76ED395 (user_id), INDEX IDX_499449493411876 (access_group_id), PRIMARY KEY(user_id, access_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_access_group ADD CONSTRAINT FK_4994494A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_access_group ADD CONSTRAINT FK_499449493411876 FOREIGN KEY (access_group_id) REFERENCES access_group (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_access_group DROP FOREIGN KEY FK_4994494A76ED395');
        $this->addSql('ALTER TABLE user_access_group DROP FOREIGN KEY FK_499449493411876');
        $this->addSql('DROP TABLE user_access_group');
    }
}
