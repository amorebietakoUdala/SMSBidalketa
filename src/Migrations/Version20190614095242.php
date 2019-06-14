<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190614095242 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE audit (id INT AUTO_INCREMENT NOT NULL, timestamp DATETIME NOT NULL, status VARCHAR(255) NOT NULL, response VARCHAR(1000) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE audit_contact (audit_id INT NOT NULL, contact_id INT NOT NULL, INDEX IDX_5D403348BD29F359 (audit_id), INDEX IDX_5D403348E7A1254A (contact_id), PRIMARY KEY(audit_id, contact_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE audit_contact ADD CONSTRAINT FK_5D403348BD29F359 FOREIGN KEY (audit_id) REFERENCES audit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE audit_contact ADD CONSTRAINT FK_5D403348E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE audit_contact DROP FOREIGN KEY FK_5D403348BD29F359');
        $this->addSql('DROP TABLE audit');
        $this->addSql('DROP TABLE audit_contact');
    }
}
