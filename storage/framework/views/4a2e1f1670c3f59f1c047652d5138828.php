<?php $__env->startSection('meta_title', 'Architecture & Tech Stack - ClearStay'); ?>
<?php $__env->startSection('meta_description', 'Deep dive into the ClearStay architecture: PHP 8.3, Laravel 11/12, MySQL Row Locking, Multi-tenancy, and AWS Ubuntu deployment.'); ?>

<?php $__env->startSection('content'); ?>
<section style="background: #f8fafc; padding: 60px 0 40px; border-bottom: 1px solid var(--border);">
    <div class="container" style="text-align: center; max-width: 800px;">
        <span style="background: #e0f2fe; color: #0284c7; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase;">
            Engineering & System Architecture
        </span>
        <h1 style="font-size: 38px; font-weight: 800; color: var(--text-dark); margin-top: 12px;">Technical Specification of ClearStay</h1>
        <p style="font-size: 16px; color: var(--text-muted); margin-top: 8px;">
            A complete walkthrough of the technologies, concurrency mechanisms, and infrastructure decisions behind the system.
        </p>
    </div>
</section>

<div class="container" style="padding: 60px 24px; max-width: 1000px;">
    <!-- Tech Stack Summary Table -->
    <div style="background: white; border: 1px solid var(--border); border-radius: 16px; padding: 32px; margin-bottom: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03);">
        <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 20px;">
            <i class="fa-solid fa-layer-group text-primary"></i> Technology Stack Matrix
        </h2>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Technology</th>
                        <th style="width: 25%;">Layer / Role</th>
                        <th>Implementation in ClearStay</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>PHP 8.3</strong></td>
                        <td>Backend Runtime</td>
                        <td>Typed properties, match expressions, high performance JIT-compatible syntax.</td>
                    </tr>
                    <tr>
                        <td><strong>Laravel 11/12/13</strong></td>
                        <td>Framework Core</td>
                        <td>Clean MVC architecture, Global Scopes, FormRequests, Transactions, Policies.</td>
                    </tr>
                    <tr>
                        <td><strong>MySQL / SQL</strong></td>
                        <td>Database & Transactions</td>
                        <td>Multi-tenant schema, Foreign keys, row locking (<code style="background: #e2e8f0; padding: 2px 4px;">SELECT ... FOR UPDATE</code>), analytics aggregations.</td>
                    </tr>
                    <tr>
                        <td><strong>JavaScript & jQuery</strong></td>
                        <td>Interactive Client</td>
                        <td>Live board auto-polling (10s), pop-up preview modal, quick status AJAX calls.</td>
                    </tr>
                    <tr>
                        <td><strong>Chart.js</strong></td>
                        <td>Analytics Visualization</td>
                        <td>Occupancy forecasting, RevPAR / ADR metrics, room distribution doughnut charts.</td>
                    </tr>
                    <tr>
                        <td><strong>Ubuntu Linux & AWS</strong></td>
                        <td>Cloud Deployment</td>
                        <td>EC2 / Lightsail hosting, Nginx web server, PHP-FPM, UFW firewall, Certbot SSL.</td>
                    </tr>
                    <tr>
                        <td><strong>Supervisor & Cron</strong></td>
                        <td>Background Daemon</td>
                        <td>Keep queue workers alive for asynchronous jobs and nightly audit summaries.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Deep Dive: Concurrency & Row Locking -->
    <div style="background: #fff7ed; border: 1px solid #fed7aa; border-radius: 16px; padding: 32px; margin-bottom: 40px;">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: #ea580c; color: white; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                <i class="fa-solid fa-lock"></i>
            </div>
            <div>
                <h3 style="font-size: 20px; font-weight: 800; color: #9a3412;">Concurrency Control: Preventing Double-Bookings</h3>
                <span style="font-size: 12px; color: #c2410c; font-weight: 600;">Pessimistic Row Locking (<code style="font-weight: 700;">lockForUpdate()</code>)</span>
            </div>
        </div>

        <p style="font-size: 14px; color: #7c2d12; line-height: 1.7; margin-bottom: 16px;">
            In hotel reservation management, race conditions occur when two desk agents or an automated OTA channel concurrently attempt to reserve or modify stay dates for the same room. Without synchronization, both requests evaluate the room as "available" before either commits, causing an embarrassing double-booking.
        </p>

        <p style="font-size: 14px; color: #7c2d12; line-height: 1.7; margin-bottom: 16px;">
            ClearStay solves this at the database engine level via <code style="background: white; padding: 2px 6px; border-radius: 4px; font-weight: 700; border: 1px solid #fed7aa;">DB::transaction()</code> combined with <code style="background: white; padding: 2px 6px; border-radius: 4px; font-weight: 700; border: 1px solid #fed7aa;">Room::lockForUpdate()</code>:
        </p>

        <div style="background: #1e293b; color: #e2e8f0; padding: 20px; border-radius: 10px; font-family: monospace; font-size: 13px; line-height: 1.6; overflow-x: auto;">
<span style="color: #94a3b8;">// App/Services/ReservationService.php</span><br>
<span style="color: #f472b6;">return</span> DB::transaction(<span style="color: #38bdf8;">function</span> () <span style="color: #f472b6;">use</span> ($reservationId, $checkIn, $checkOut, $targetRoomId) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #94a3b8;">// 1. Acquire exclusive row lock on the target room</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;$room = Room::where(<span style="color: #a7f3d0;">'id'</span>, $targetRoomId)-><span style="color: #fbbf24;">lockForUpdate()</span>->firstOrFail();<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #94a3b8;">// 2. Query overlapping bookings with row locks</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;$conflict = Reservation::where(<span style="color: #a7f3d0;">'room_id'</span>, $targetRoomId)<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;->whereIn(<span style="color: #a7f3d0;">'status'</span>, [<span style="color: #a7f3d0;">'confirmed'</span>, <span style="color: #a7f3d0;">'checked_in'</span>])<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;->where(<span style="color: #a7f3d0;">'check_in_date'</span>, <span style="color: #a7f3d0;">'&lt;'</span>, $checkOut)<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;->where(<span style="color: #a7f3d0;">'check_out_date'</span>, <span style="color: #a7f3d0;">'&gt;'</span>, $checkIn)<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-><span style="color: #fbbf24;">lockForUpdate()</span>->first();<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #f472b6;">if</span> ($conflict) {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #f472b6;">throw</span> ValidationException::withMessages([<span style="color: #a7f3d0;">'room'</span> => [<span style="color: #a7f3d0;">'Room is already reserved for these dates.'</span>]]);<br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #94a3b8;">// 3. Update dates and record audit trail within serialized transaction</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;$reservation->update([<span style="color: #a7f3d0;">'check_in_date'</span> => $checkIn, <span style="color: #a7f3d0;">'check_out_date'</span> => $checkOut]);<br>
&nbsp;&nbsp;&nbsp;&nbsp;ReservationChange::create([...]);<br>
});
        </div>
    </div>

    <!-- Multi-Tenancy Architecture -->
    <div style="background: white; border: 1px solid var(--border); border-radius: 16px; padding: 32px;">
        <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 14px;">
            <i class="fa-solid fa-network-wired text-primary"></i> Multi-Tenant Architecture via Global Scopes
        </h2>
        <p style="font-size: 14px; color: var(--text-muted); line-height: 1.7; margin-bottom: 16px;">
            ClearStay features an enterprise multi-tenant design where every domain entity (<code style="color: var(--text-dark); font-weight: 600;">rooms</code>, <code style="color: var(--text-dark); font-weight: 600;">room_types</code>, <code style="color: var(--text-dark); font-weight: 600;">reservations</code>, <code style="color: var(--text-dark); font-weight: 600;">guests</code>, <code style="color: var(--text-dark); font-weight: 600;">housekeeping_tasks</code>) carries a foreign key to <code style="color: var(--text-dark); font-weight: 600;">hotels(id)</code>.
        </p>
        <p style="font-size: 14px; color: var(--text-muted); line-height: 1.7;">
            Through an Eloquent Global Scope (<code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 600;">TenantScope</code>) and trait (<code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: 600;">BelongsToHotel</code>), queries automatically scope to the authenticated user's assigned property, completely eliminating accidental cross-tenant data leaks.
        </p>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/public/features.blade.php ENDPATH**/ ?>