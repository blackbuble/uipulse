<?php

namespace App\Services;

use App\Models\Component;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubIntegrationService
{
    private string $token;
    private string $baseUrl = 'https://api.github.com';

    public function __construct()
    {
        $this->token = config('services.github.token');
    }

    /**
     * Create a pull request with component code.
     */
    public function createComponentPR(
        Component $component,
        string $repository,
        string $branch,
        string $framework = 'react'
    ): array {
        Log::info("Creating GitHub PR for component: {$component->name}");

        try {
            // Generate component code
            $codeService = app(CodeGenerationService::class);
            $codeData = $codeService->generateCode($component, $framework);

            // Create a new branch
            $newBranch = $this->createBranch($repository, $branch, $component);

            // Create file in the branch
            $filePath = $this->getComponentPath($component, $framework);
            $this->createFile($repository, $newBranch, $filePath, $codeData['code']);

            // Create pull request
            $pr = $this->createPullRequest(
                $repository,
                $newBranch,
                $branch,
                $component
            );

            Log::info("GitHub PR created successfully", [
                'pr_number' => $pr['number'],
                'pr_url' => $pr['html_url'],
            ]);

            return $pr;

        } catch (\Exception $e) {
            Log::error("Failed to create GitHub PR", [
                'error' => $e->getMessage(),
                'component' => $component->name,
            ]);

            throw $e;
        }
    }

    /**
     * Create a new branch.
     */
    private function createBranch(string $repository, string $baseBranch, Component $component): string
    {
        // Get base branch SHA
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/repos/{$repository}/git/ref/heads/{$baseBranch}");

        if (!$response->successful()) {
            throw new \Exception("Failed to get base branch: " . $response->body());
        }

        $baseSha = $response->json()['object']['sha'];

        // Create new branch
        $newBranch = 'feature/add-' . str_replace(' ', '-', strtolower($component->name)) . '-component';

        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/repos/{$repository}/git/refs", [
                'ref' => "refs/heads/{$newBranch}",
                'sha' => $baseSha,
            ]);

        if (!$response->successful() && !str_contains($response->body(), 'already exists')) {
            throw new \Exception("Failed to create branch: " . $response->body());
        }

        return $newBranch;
    }

    /**
     * Create a file in the repository.
     */
    private function createFile(string $repository, string $branch, string $path, string $content): void
    {
        $response = Http::withToken($this->token)
            ->put("{$this->baseUrl}/repos/{$repository}/contents/{$path}", [
                'message' => "Add {$path}",
                'content' => base64_encode($content),
                'branch' => $branch,
            ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to create file: " . $response->body());
        }
    }

    /**
     * Create a pull request.
     */
    private function createPullRequest(
        string $repository,
        string $head,
        string $base,
        Component $component
    ): array {
        $title = "Add {$component->name} component";
        $body = $this->generatePRDescription($component);

        $response = Http::withToken($this->token)
            ->post("{$this->baseUrl}/repos/{$repository}/pulls", [
                'title' => $title,
                'head' => $head,
                'base' => $base,
                'body' => $body,
            ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to create PR: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Generate PR description.
     */
    private function generatePRDescription(Component $component): string
    {
        $description = "## Component: {$component->name}\n\n";
        $description .= "**Type:** {$component->type}\n";
        $description .= "**Category:** {$component->category}\n\n";

        if ($component->description) {
            $description .= "### Description\n{$component->description}\n\n";
        }

        $description .= "### Properties\n";
        $description .= "```json\n" . json_encode($component->properties, JSON_PRETTY_PRINT) . "\n```\n\n";

        $description .= "### Variants\n";
        $variantCount = $component->variants()->count();
        $description .= "This component has {$variantCount} variant(s).\n\n";

        $description .= "---\n";
        $description .= "*Generated by UIPulse*";

        return $description;
    }

    /**
     * Get component file path.
     */
    private function getComponentPath(Component $component, string $framework): string
    {
        $name = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $component->name)));

        return match ($framework) {
            'react' => "src/components/{$name}/{$name}.tsx",
            'vue' => "src/components/{$name}.vue",
            'html' => "components/{$name}.html",
            default => "components/{$name}.txt",
        };
    }

    /**
     * List repositories for authenticated user.
     */
    public function listRepositories(): array
    {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/user/repos", [
                'sort' => 'updated',
                'per_page' => 100,
            ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to list repositories: " . $response->body());
        }

        return collect($response->json())
            ->map(fn($repo) => [
                'name' => $repo['full_name'],
                'description' => $repo['description'],
                'private' => $repo['private'],
            ])
            ->toArray();
    }

    /**
     * Get repository branches.
     */
    public function getBranches(string $repository): array
    {
        $response = Http::withToken($this->token)
            ->get("{$this->baseUrl}/repos/{$repository}/branches");

        if (!$response->successful()) {
            throw new \Exception("Failed to get branches: " . $response->body());
        }

        return collect($response->json())
            ->pluck('name')
            ->toArray();
    }
}
