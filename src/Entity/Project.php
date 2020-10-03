<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProjectRepository::class)
 */
class Project
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
    private $code;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $branchRegexp;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="mainProjects")
     * @ORM\JoinColumn(nullable=false)
     */
    private $maintainer;

    /**
     * @ORM\OneToMany(targetEntity=Redmine::class, mappedBy="project")
     */
    private $redmines;

    /**
     * @ORM\OneToMany(targetEntity=Test::class, mappedBy="project")
     */
    private $tests;

    public function __construct()
    {
        $this->redmines = new ArrayCollection();
        $this->tests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
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

    public function getBranchRegexp(): ?string
    {
        return $this->branchRegexp;
    }

    public function setBranchRegexp(?string $branchRegexp): self
    {
        $this->branchRegexp = $branchRegexp;

        return $this;
    }

    public function getMaintainer(): ?User
    {
        return $this->maintainer;
    }

    public function setMaintainer(?User $maintainer): self
    {
        $this->maintainer = $maintainer;

        return $this;
    }

    /**
     * @return Collection|Redmine[]
     */
    public function getRedmines(): Collection
    {
        return $this->redmines;
    }

    public function addRedmine(Redmine $redmine): self
    {
        if (!$this->redmines->contains($redmine)) {
            $this->redmines[] = $redmine;
            $redmine->setProject($this);
        }

        return $this;
    }

    public function removeRedmine(Redmine $redmine): self
    {
        if ($this->redmines->contains($redmine)) {
            $this->redmines->removeElement($redmine);
            // set the owning side to null (unless already changed)
            if ($redmine->getProject() === $this) {
                $redmine->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Test[]
     */
    public function getTests(): Collection
    {
        return $this->tests;
    }

    public function addTest(Test $test): self
    {
        if (!$this->tests->contains($test)) {
            $this->tests[] = $test;
            $test->setProject($this);
        }

        return $this;
    }

    public function removeTest(Test $test): self
    {
        if ($this->tests->contains($test)) {
            $this->tests->removeElement($test);
            // set the owning side to null (unless already changed)
            if ($test->getProject() === $this) {
                $test->setProject(null);
            }
        }

        return $this;
    }
}
