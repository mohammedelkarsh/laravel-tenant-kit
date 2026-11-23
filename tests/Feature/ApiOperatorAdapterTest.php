<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiOperatorAdapterTest extends TestCase
{
    public function test_adapter_yaml_exists_and_defines_required_tools(): void
    {
        $path = base_path('integrations/api-operator/adapter.yaml');
        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString('name: tenant_kit', $contents);

        foreach ([
            'list_workspaces',
            'create_workspace',
            'get_workspace',
            'get_subscription',
            'get_usage',
            'invite_team_member',
        ] as $tool) {
            $this->assertStringContainsString("name: {$tool}", $contents, "Missing tool: {$tool}");
        }
    }

    public function test_api_operator_docs_exist(): void
    {
        $this->assertFileExists(base_path('docs/api-operator.md'));
        $this->assertFileExists(base_path('integrations/api-operator/README.md'));
        $this->assertFileExists(base_path('integrations/api-operator/policy.yaml'));
    }

    public function test_adapter_documents_api_operator_auth_env_var(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertIsString($envExample);
        $this->assertStringContainsString('TENANT_KIT_API_TOKEN', $envExample);
        $this->assertStringContainsString('API_OPERATOR_ENABLED', $envExample);
    }
}
