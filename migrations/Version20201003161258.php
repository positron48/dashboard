<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201003161258 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, maintainer_id INT NOT NULL, code VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, branch_regexp VARCHAR(255) DEFAULT NULL, INDEX IDX_2FB3D0EE85D19953 (maintainer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE redmine (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, url VARCHAR(255) NOT NULL, api_key VARCHAR(255) NOT NULL, INDEX IDX_9AFC5D97166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE test (id INT AUTO_INCREMENT NOT NULL, project_id INT NOT NULL, name VARCHAR(255) NOT NULL, script_url VARCHAR(255) NOT NULL, comment VARCHAR(255) DEFAULT NULL, INDEX IDX_D87F7E0C166D1F9C (project_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE test_domain (id INT AUTO_INCREMENT NOT NULL, test_id INT NOT NULL, code VARCHAR(255) NOT NULL, domain VARCHAR(255) NOT NULL, INDEX IDX_307019251E5D0459 (test_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, api_key VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE85D19953 FOREIGN KEY (maintainer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE redmine ADD CONSTRAINT FK_9AFC5D97166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE test ADD CONSTRAINT FK_D87F7E0C166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE test_domain ADD CONSTRAINT FK_307019251E5D0459 FOREIGN KEY (test_id) REFERENCES test (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE redmine DROP FOREIGN KEY FK_9AFC5D97166D1F9C');
        $this->addSql('ALTER TABLE test DROP FOREIGN KEY FK_D87F7E0C166D1F9C');
        $this->addSql('ALTER TABLE test_domain DROP FOREIGN KEY FK_307019251E5D0459');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE85D19953');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE redmine');
        $this->addSql('DROP TABLE test');
        $this->addSql('DROP TABLE test_domain');
        $this->addSql('DROP TABLE user');
    }
}
