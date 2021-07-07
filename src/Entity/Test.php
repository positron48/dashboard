<?php

namespace App\Entity;

use App\Repository\TestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TestRepository::class)
 */
class Test
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $scriptUrl;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="tests")
     * @ORM\JoinColumn(nullable=false)
     */
    private $project;

    /**
     * @ORM\OneToMany(targetEntity=TestDomain::class, mappedBy="test", orphanRemoval=true)
     */
    private $testDomains;

    public function __construct()
    {
        $this->testDomains = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getScriptUrl(): ?string
    {
        return $this->scriptUrl;
    }

    public function setScriptUrl(string $scriptUrl): self
    {
        $this->scriptUrl = $scriptUrl;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    /**
     * @return Collection|TestDomain[]
     */
    public function getTestDomains(): Collection
    {
        return $this->testDomains;
    }

    public function addTestDomain(TestDomain $testDomain): self
    {
        if (!$this->testDomains->contains($testDomain)) {
            $this->testDomains[] = $testDomain;
            $testDomain->setTest($this);
        }

        return $this;
    }

    public function removeTestDomain(TestDomain $testDomain): self
    {
        if ($this->testDomains->contains($testDomain)) {
            $this->testDomains->removeElement($testDomain);
            // set the owning side to null (unless already changed)
            if ($testDomain->getTest() === $this) {
                $testDomain->setTest(null);
            }
        }

        return $this;
    }
}
