<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for ClearStay 2.0 Enterprise Platform.
     */
    public function up(): void
    {
        // 1. Extend users table for access governance & departments
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('role');
            }
            if (!Schema::hasColumn('users', 'access_expires_at')) {
                $table->timestamp('access_expires_at')->nullable()->after('lockout_until');
            }
            if (!Schema::hasColumn('users', 'is_suspended')) {
                $table->boolean('is_suspended')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('users', 'suspension_reason')) {
                $table->string('suspension_reason')->nullable()->after('is_suspended');
            }
            if (!Schema::hasColumn('users', 'suspended_until')) {
                $table->timestamp('suspended_until')->nullable()->after('suspension_reason');
            }
        });

        // 2. Channels (OTA & Direct Distribution endpoints)
        if (!Schema::hasTable('channels')) {
            Schema::create('channels', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique(); // booking_com, agoda, expedia, rakuten, jalan, direct_web, demo_ota
                $table->string('type')->default('ota'); // ota, direct, metasearch, gds
                $table->string('adapter_class')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('api_endpoint')->nullable();
                $table->json('settings')->nullable();
                $table->timestamp('last_sync_at')->nullable();
                $table->string('status')->default('connected'); // connected, syncing, error, disconnected
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        // 3. Channel Connections (Property-level authorization & credentials)
        if (!Schema::hasTable('channel_connections')) {
            Schema::create('channel_connections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_enabled')->default(true);
                $table->text('credentials')->nullable(); // encrypted JSON
                $table->integer('sync_interval_mins')->default(15);
                $table->timestamp('last_successful_sync')->nullable();
                $table->string('sync_status')->default('idle'); // idle, syncing, ok, failed, reconciliation_needed
                $table->integer('error_count')->default(0);
                $table->timestamps();

                $table->unique(['hotel_id', 'channel_id']);
            });
        }

        // 4. Channel Room & Rate Mappings
        if (!Schema::hasTable('channel_room_mappings')) {
            Schema::create('channel_room_mappings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('channel_connection_id')->constrained()->cascadeOnDelete();
                $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
                $table->string('channel_room_code');
                $table->string('channel_rate_plan_code')->nullable();
                $table->decimal('rate_multiplier', 5, 2)->default(1.00); // price adjustments per channel
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 5. Centralized Multi-Day Inventory & Restrictions
        if (!Schema::hasTable('central_inventories')) {
            Schema::create('central_inventories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
                $table->date('date');
                $table->integer('total_units')->default(10);
                $table->integer('available_units')->default(10);
                $table->integer('reserved_units')->default(0);
                $table->integer('maintenance_units')->default(0);
                $table->decimal('base_rate', 10, 2)->default(12000.00);
                $table->string('currency', 3)->default('JPY');
                $table->integer('min_stay')->default(1);
                $table->integer('max_stay')->default(30);
                $table->boolean('closed_to_arrival')->default(false);
                $table->boolean('closed_to_departure')->default(false);
                $table->boolean('stop_sell')->default(false);
                $table->integer('version')->default(1);
                $table->timestamps();

                $table->unique(['hotel_id', 'room_type_id', 'date']);
                $table->index(['hotel_id', 'date']);
            });
        }

        // 6. Transactional Outbox for Channel Synchronization
        if (!Schema::hasTable('sync_outbox_events')) {
            Schema::create('sync_outbox_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('channel_connection_id')->nullable()->constrained()->nullOnDelete();
                $table->string('correlation_id')->index();
                $table->string('event_type'); // inventory_sync, rate_sync, restriction_sync, bulk_update
                $table->json('payload');
                $table->string('status')->default('pending'); // pending, processing, confirmed, failed, retrying, requires_reconciliation
                $table->integer('attempts')->default(0);
                $table->text('last_error')->nullable();
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamps();

                $table->index(['hotel_id', 'status']);
            });
        }

        // 7. Synchronization Attempt Logs
        if (!Schema::hasTable('sync_attempt_logs')) {
            Schema::create('sync_attempt_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sync_outbox_event_id')->constrained()->cascadeOnDelete();
                $table->integer('attempt_number')->default(1);
                $table->integer('http_status')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('response_payload')->nullable();
                $table->integer('execution_time_ms')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        // 8. Bulk Change Batches & Rollback Audit
        if (!Schema::hasTable('bulk_change_batches')) {
            Schema::create('bulk_change_batches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('change_type'); // rate_change, inventory_change, stop_sell, restriction
                $table->text('summary');
                $table->json('scope_criteria');
                $table->json('previous_state');
                $table->json('new_state');
                $table->string('status')->default('applied'); // applied, rolled_back, failed
                $table->timestamp('rolled_back_at')->nullable();
                $table->foreignId('rolled_back_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 9. Rate Plans & Strategies
        if (!Schema::hasTable('rate_plans')) {
            Schema::create('rate_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('code')->unique();
                $table->decimal('base_price', 10, 2);
                $table->string('pricing_model')->default('flat'); // flat, occupancy_based, seasonal
                $table->string('cancellation_policy')->default('free_24h');
                $table->string('meal_plan')->default('room_only'); // room_only, breakfast, half_board
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 10. Japanese Calendar Intelligence & Demand Events
        if (!Schema::hasTable('calendar_events')) {
            Schema::create('calendar_events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('title_ja');
                $table->string('event_type'); // national_holiday, golden_week, obon, new_year, festival
                $table->date('start_date');
                $table->date('end_date');
                $table->decimal('demand_multiplier', 4, 2)->default(1.20);
                $table->integer('recommended_min_stay')->default(1);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['start_date', 'end_date']);
            });
        }

        // 11. Rate Parity Observations
        if (!Schema::hasTable('rate_parity_observations')) {
            Schema::create('rate_parity_observations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
                $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
                $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
                $table->date('date');
                $table->decimal('expected_rate', 10, 2);
                $table->decimal('observed_rate', 10, 2);
                $table->decimal('difference_amount', 10, 2);
                $table->boolean('is_discrepancy')->default(false);
                $table->timestamp('observed_at');
                $table->json('details')->nullable();
                $table->timestamps();
            });
        }

        // 12. Partner Registry (TEMAIRAZU-style Partner Assurance)
        if (!Schema::hasTable('integration_partners')) {
            Schema::create('integration_partners', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('company');
                $table->string('category'); // pms, ota, crs, rms, booking_engine, channel_manager
                $table->text('description')->nullable();
                $table->string('api_base_url')->nullable();
                $table->string('api_version')->default('v2');
                $table->string('auth_type')->default('bearer'); // bearer, basic, api_key, oauth2
                $table->json('supported_capabilities')->nullable();
                $table->string('status')->default('active'); // active, testing, certified, deprecated
                $table->string('contact_email')->nullable();
                $table->timestamps();
            });
        }

        // 13. Integration Contracts (OpenAPI & JSON Schema validation)
        if (!Schema::hasTable('partner_contracts')) {
            Schema::create('partner_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_id')->constrained('integration_partners')->cascadeOnDelete();
                $table->string('contract_name');
                $table->string('schema_format')->default('openapi3'); // openapi3, jsonschema
                $table->longText('spec_content');
                $table->string('version')->default('1.0.0');
                $table->boolean('is_active')->default(true);
                $table->json('validation_rules')->nullable();
                $table->timestamps();
            });
        }

        // 14. Integration Test Suites & Scenarios
        if (!Schema::hasTable('integration_test_suites')) {
            Schema::create('integration_test_suites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_id')->constrained('integration_partners')->cascadeOnDelete();
                $table->string('suite_name');
                $table->string('category')->default('reservations'); // reservations, inventory, rates, failures
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('integration_test_cases')) {
            Schema::create('integration_test_cases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('test_suite_id')->constrained('integration_test_suites')->cascadeOnDelete();
                $table->string('case_code');
                $table->string('name');
                $table->text('description')->nullable();
                $table->json('request_payload');
                $table->integer('expected_status_code')->default(200);
                $table->json('expected_assertions')->nullable();
                $table->boolean('is_simulation_safe')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('integration_test_runs')) {
            Schema::create('integration_test_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('test_suite_id')->constrained('integration_test_suites')->cascadeOnDelete();
                $table->foreignId('partner_id')->constrained('integration_partners')->cascadeOnDelete();
                $table->foreignId('run_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('running'); // running, passed, failed, partial
                $table->integer('total_cases')->default(0);
                $table->integer('passed_cases')->default(0);
                $table->integer('failed_cases')->default(0);
                $table->integer('duration_ms')->default(0);
                $table->json('results_summary')->nullable();
                $table->timestamps();
            });
        }

        // 15. EventTrace (Transaction-level timeline)
        if (!Schema::hasTable('event_traces')) {
            Schema::create('event_traces', function (Blueprint $table) {
                $table->id();
                $table->string('trace_id')->index();
                $table->foreignId('partner_id')->nullable()->constrained('integration_partners')->nullOnDelete();
                $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
                $table->string('operation'); // reservation_create, inventory_push, rate_query, webhook_ack
                $table->string('stage'); // request_received, auth_verified, schema_validated, business_logic, external_dispatched, ack_received, retry_triggered, completed
                $table->string('status')->default('success'); // success, failure, warning
                $table->integer('duration_ms')->default(0);
                $table->json('request_headers')->nullable();
                $table->json('request_body')->nullable();
                $table->json('response_headers')->nullable();
                $table->json('response_body')->nullable();
                $table->json('error_details')->nullable();
                $table->boolean('replayable')->default(true);
                $table->timestamps();

                $table->index(['trace_id', 'created_at']);
            });
        }

        // 16. Partner Readiness Reports
        if (!Schema::hasTable('partner_readiness_reports')) {
            Schema::create('partner_readiness_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('partner_id')->constrained('integration_partners')->cascadeOnDelete();
                $table->string('report_code')->unique();
                $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('overall_status')->default('in_progress'); // passed, passed_with_limitations, failed, in_progress
                $table->integer('score_percentage')->default(0);
                $table->text('summary');
                $table->json('category_breakdown')->nullable();
                $table->json('outstanding_issues')->nullable();
                $table->json('evidence_data')->nullable();
                $table->timestamps();
            });
        }

        // 17. Intelligent Access Governance: Permissions Library
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique(); // e.g. reservations.view, inventory.edit
                $table->string('category'); // properties, reservations, inventory, rates, channels, partners, integration, reports, security, staff
                $table->string('description');
                $table->string('description_ja')->nullable();
                $table->timestamps();
            });
        }

        // 18. Individual Employee Permission Overrides (Explicit Allow / Deny & Temporary Grants)
        if (!Schema::hasTable('user_permissions')) {
            Schema::create('user_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
                $table->enum('access_type', ['allow', 'deny'])->default('allow');
                $table->boolean('is_temporary')->default(false);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('reason')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'permission_id']);
            });
        }

        // 19. Device Trust Management
        if (!Schema::hasTable('trusted_devices')) {
            Schema::create('trusted_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('device_identifier')->index();
                $table->string('device_name')->default('Workstation');
                $table->string('browser')->nullable();
                $table->string('os')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('trust_status')->default('new'); // trusted, new, verification_required, revoked
                $table->timestamp('first_seen_at');
                $table->timestamp('last_seen_at');
                $table->timestamp('revoked_at')->nullable();
                $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 20. Security Risk Events & Dynamic Risk Scoring (0-100)
        if (!Schema::hasTable('security_risk_events')) {
            Schema::create('security_risk_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('event_type'); // new_device, rapid_cancellations, failed_logins, cross_hotel_attempt, abnormal_export
                $table->integer('risk_score')->default(10); // 0 - 100
                $table->string('risk_band')->default('low'); // low, medium, high, critical
                $table->json('contributing_factors');
                $table->string('action_taken')->default('allow'); // allow, monitor, require_mfa, require_approval, deny, auto_block
                $table->boolean('is_reviewed')->default(false);
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('investigation_notes')->nullable();
                $table->timestamps();
            });
        }

        // 21. Authorization Decision Audit Log
        if (!Schema::hasTable('authorization_decisions')) {
            Schema::create('authorization_decisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action');
                $table->string('resource_type')->nullable();
                $table->string('resource_id')->nullable();
                $table->string('decision'); // ALLOW, DENY, REQUIRE_MFA, REQUIRE_MANAGER_APPROVAL, RESTRICTED_PENDING_REVIEW
                $table->text('explanation');
                $table->json('evaluated_context')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }

        // 22. AI-Assisted Security Governance Recommendations
        if (!Schema::hasTable('ai_governance_recommendations')) {
            Schema::create('ai_governance_recommendations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hotel_id')->nullable()->constrained('hotels')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('category'); // incident_summary, excessive_permissions, failed_logins, risk_analysis
                $table->string('title');
                $table->text('recommendation_text');
                $table->string('severity')->default('info'); // info, warning, high, critical
                $table->string('status')->default('pending_review'); // pending_review, acknowledged, dismissed, actioned
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_governance_recommendations');
        Schema::dropIfExists('authorization_decisions');
        Schema::dropIfExists('security_risk_events');
        Schema::dropIfExists('trusted_devices');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('partner_readiness_reports');
        Schema::dropIfExists('event_traces');
        Schema::dropIfExists('integration_test_runs');
        Schema::dropIfExists('integration_test_cases');
        Schema::dropIfExists('integration_test_suites');
        Schema::dropIfExists('partner_contracts');
        Schema::dropIfExists('integration_partners');
        Schema::dropIfExists('rate_parity_observations');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('rate_plans');
        Schema::dropIfExists('bulk_change_batches');
        Schema::dropIfExists('sync_attempt_logs');
        Schema::dropIfExists('sync_outbox_events');
        Schema::dropIfExists('central_inventories');
        Schema::dropIfExists('channel_room_mappings');
        Schema::dropIfExists('channel_connections');
        Schema::dropIfExists('channels');
    }
};
