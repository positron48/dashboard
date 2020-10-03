<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User
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
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $apiKey;

    /**
     * @ORM\OneToMany(targetEntity=Project::class, mappedBy="maintainer")
     */
    private $mainProjects;

    public function __construct()
    {
        $this->mainProjects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * @return Collection|Project[]
     */
    public function getMainProjects(): Collection
    {
        return $this->mainProjects;
    }

    public function addMainProject(Project $mainProject): self
    {
        if (!$this->mainProjects->contains($mainProject)) {
            $this->mainProjects[] = $mainProject;
            $mainProject->setMaintainer($this);
        }

        return $this;
    }

    public function removeMainProject(Project $mainProject): self
    {
        if ($this->mainProjects->contains($mainProject)) {
            $this->mainProjects->removeElement($mainProject);
            // set the owning side to null (unless already changed)
            if ($mainProject->getMaintainer() === $this) {
                $mainProject->setMaintainer(null);
            }
        }

        return $this;
    }
}
