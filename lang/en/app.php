<?php

return [

    'nav' => [
        'dashboard' => 'Dashboard',
        'log_in' => 'Log in',
        'create_workspace' => 'Create workspace',
        'team' => 'Team',
        'billing' => 'Billing',
    ],

    'landing' => [
        'title' => 'Multi-tenant SaaS Starter',
        'meta_description' => 'Laravel multi-tenant starter with isolated databases, Breeze, Filament, teams, and Stripe billing.',
        'hero_title' => 'Multi-tenant SaaS starter for',
        'hero_highlight' => 'Laravel',
        'hero_subtitle' => 'Isolated DB per workspace, subdomain routing, teams, Stripe billing, Filament admin.',
        'live_demo' => 'Live demo',
        'architecture' => 'Architecture',
        'central' => 'Central',
        'central_desc' => 'Admin, billing, workspace signup',
        'tenant' => 'Tenant',
        'tenant_desc' => 'Auth, teams, isolated database',
        'included' => 'Included',
        'features' => [
            'multi_tenancy' => ['title' => 'Multi-tenancy', 'desc' => 'Isolated DB, subdomain & custom domains'],
            'authentication' => ['title' => 'Authentication', 'desc' => 'Breeze on central & tenant'],
            'teams' => ['title' => 'Teams & roles', 'desc' => 'Owner, admin, member + invites'],
            'billing' => ['title' => 'Stripe billing', 'desc' => 'Cashier per workspace'],
            'filament' => ['title' => 'Filament admin', 'desc' => 'Manage workspaces at /admin'],
            'cli' => ['title' => 'CLI', 'desc' => 'tenant:provision command'],
        ],
        'try_demo' => 'Try the demo',
        'admin_panel' => 'Admin panel',
        'demo_login' => 'Demo login',
    ],

    'dashboard' => [
        'title' => 'Dashboard',
        'admin_panel' => 'Admin panel',
        'create_workspace' => 'Create workspace',
        'demo_workspace' => 'Demo workspace',
    ],

    'tenant' => [
        'sign_in' => 'Sign in',
        'register' => 'Register',
        'members' => 'Members',
        'url' => 'URL',
        'suspended' => 'This workspace has been suspended. Contact support for help.',
    ],

    'workspace' => [
        'create_title' => 'Create your workspace',
        'create_subtitle' => 'Start a new tenant on :domain',
        'name' => 'Workspace name',
        'url' => 'Workspace URL',
        'create_button' => 'Create workspace',
        'created' => 'Your workspace has been created successfully.',
    ],

    'team' => [
        'title' => 'Team members',
        'members' => 'Members',
        'pending_invitations' => 'Pending invitations',
        'invite_title' => 'Invite a teammate',
        'email' => 'Email',
        'role' => 'Role',
        'role_member' => 'Member',
        'role_admin' => 'Admin',
        'send_invitation' => 'Send invitation',
        'member_exists' => 'This user is already a workspace member.',
        'invitation_sent' => 'Invitation sent to :email',
        'invitation_subject' => 'Workspace invitation',
        'invitation_body' => 'You have been invited to join :workspace. Accept here: :url',
    ],

    'invitations' => [
        'expired' => 'This invitation has expired.',
        'register_to_accept' => 'Create an account to accept your invitation.',
        'wrong_email' => 'Sign in with :email to accept this invitation.',
        'accepted' => 'Welcome to the team!',
    ],

    'billing' => [
        'title' => 'Billing — :name',
        'checkout_success' => 'Subscription updated successfully.',
        'stripe_not_configured' => 'Stripe is not configured. Add keys to .env (see README).',
        'workspace' => 'Workspace',
        'active_subscription' => 'Active subscription: :price',
        'manage_payment' => 'Manage payment method & invoices →',
        'no_subscription' => 'No active subscription.',
        'per_month' => '/mo',
        'subscribe' => 'Subscribe',
        'back_to_workspace' => '← Back to workspace',
        'stripe_not_configured_error' => 'Stripe is not configured.',
    ],

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'description' => 'For small teams getting started.',
        ],
        'pro' => [
            'name' => 'Pro',
            'description' => 'For growing teams that need more power.',
        ],
    ],

    'oauth' => [
        'or_continue_with' => 'Or continue with',
        'google' => 'Google',
        'github' => 'GitHub',
    ],

    'filament' => [
        'analytics_total_workspaces' => 'Total workspaces',
        'analytics_new_this_month' => ':count new this month',
        'analytics_active_subscriptions' => 'Active subscriptions',
        'analytics_stripe_subscribers' => 'Stripe subscribers',
        'analytics_platform_users' => 'Platform users',
        'analytics_central_users' => 'Central app accounts',
        'analytics_growth_chart' => 'Workspace growth (6 months)',
        'workspaces' => 'Workspaces',
        'workspace' => 'workspace',
        'workspaces_plural' => 'workspaces',
        'subdomain' => 'Subdomain',
        'url' => 'URL',
        'open' => 'Open',
        'domains' => 'Domains',
        'domain' => 'Domain',
        'domain_helper' => 'Use the subdomain slug (e.g. demo) or a full custom domain (e.g. app.acme.com).',
        'type' => 'Type',
        'subdomain_type' => 'Subdomain',
        'custom_domain_type' => 'Custom domain',
        'stats_workspaces' => 'Workspaces',
        'stats_workspaces_desc' => 'Total tenant workspaces',
        'stats_admins' => 'Platform admins',
        'stats_admins_desc' => 'Central app users',
        'stats_domain' => 'Central domain',
        'stats_domain_desc' => 'Subdomains are created under this host',
        'status' => 'Status',
        'suspend' => 'Suspend',
        'unsuspend' => 'Unsuspend',
        'suspended' => 'Suspended',
        'active' => 'Active',
    ],

];
