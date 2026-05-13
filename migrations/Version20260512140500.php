<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512140500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE intranet_page_access_group DROP FOREIGN KEY FK_DE68BDE193411876');
        $this->addSql('ALTER TABLE intranet_page_access_group DROP FOREIGN KEY FK_DE68BDE1B85FFD4F');
        $this->addSql('ALTER TABLE user_access_group DROP FOREIGN KEY FK_499449493411876');
        $this->addSql('ALTER TABLE user_access_group DROP FOREIGN KEY FK_4994494A76ED395');
        $this->addSql('DROP TABLE access_group');
        $this->addSql('DROP TABLE intranet_page_access_group');
        $this->addSql('DROP TABLE intranet_page');
        $this->addSql('DROP TABLE user_access_group');
        $this->addSql('ALTER TABLE aggrid_option ADD editable TINYINT(1) DEFAULT NULL, ADD cell_editor VARCHAR(255) DEFAULT NULL, ADD cell_editor_params JSON DEFAULT NULL, CHANGE compatator comparator VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE access_group (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE intranet_page_access_group (intranet_page_id INT NOT NULL, access_group_id INT NOT NULL, INDEX IDX_DE68BDE193411876 (access_group_id), INDEX IDX_DE68BDE1B85FFD4F (intranet_page_id), PRIMARY KEY(intranet_page_id, access_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE intranet_page (id INT AUTO_INCREMENT NOT NULL, route_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, label VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_B37F672BF3667F83 (route_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_access_group (user_id INT NOT NULL, access_group_id INT NOT NULL, INDEX IDX_499449493411876 (access_group_id), INDEX IDX_4994494A76ED395 (user_id), PRIMARY KEY(user_id, access_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE intranet_page_access_group ADD CONSTRAINT FK_DE68BDE193411876 FOREIGN KEY (access_group_id) REFERENCES access_group (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE intranet_page_access_group ADD CONSTRAINT FK_DE68BDE1B85FFD4F FOREIGN KEY (intranet_page_id) REFERENCES intranet_page (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_access_group ADD CONSTRAINT FK_499449493411876 FOREIGN KEY (access_group_id) REFERENCES access_group (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_access_group ADD CONSTRAINT FK_4994494A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE aggrid_option ADD compatator VARCHAR(255) DEFAULT NULL, DROP comparator, DROP editable, DROP cell_editor, DROP cell_editor_params');
    }
}
