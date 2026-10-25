<?php $__env->startSection('title', __('clearstay.nav.dashboard')); ?>
<?php $__env->startSection('header_title', $hotel ? $hotel->name : __('clearstay.nav.dashboard')); ?>
<?php $__env->startSection('header_subtitle', __('clearstay.description')); ?>

<?php $__env->startSection('content'); ?>
<!-- KPI Metric Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 28px;">
    <!-- Occupancy Rate -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;"><?php echo e(__('clearstay.kpis.occupancy_rate')); ?></span>
                    <h3 style="font-size: 28px; font-weight: 800; color: var(--primary); margin-top: 6px;"><?php echo e($kpis['occupancy_rate']); ?>%</h3>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fa-solid fa-percent"></i>
                </div>
            </div>
            <div style="margin-top: 12px; background: #e2e8f0; border-radius: 9999px; height: 6px; overflow: hidden;">
                <div style="background: var(--primary); height: 100%; width: <?php echo e(min(100, $kpis['occupancy_rate'])); ?>%;"></div>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 8px;"><?php echo e($kpis['occupied_rooms']); ?> of <?php echo e($kpis['total_rooms']); ?> rooms active today</p>
        </div>
    </div>

    <!-- Available Rooms -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;"><?php echo e(__('clearstay.kpis.available_rooms')); ?></span>
                    <h3 style="font-size: 28px; font-weight: 800; color: #059669; margin-top: 6px;"><?php echo e($kpis['available_rooms']); ?></h3>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fa-solid fa-bed"></i>
                </div>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 26px;">Clean & ready for immediate walk-in</p>
        </div>
    </div>

    <!-- Today Arrivals -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;"><?php echo e(__('clearstay.kpis.today_arrivals')); ?></span>
                    <h3 style="font-size: 28px; font-weight: 800; color: #d97706; margin-top: 6px;"><?php echo e($kpis['today_arrivals']); ?></h3>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fa-solid fa-plane-arrival"></i>
                </div>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 26px;">Expected front-desk check-ins today</p>
        </div>
    </div>

    <!-- Monthly Revenue & ADR -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;"><?php echo e(__('clearstay.kpis.monthly_revenue')); ?></span>
                    <h3 style="font-size: 24px; font-weight: 800; color: var(--text-dark); margin-top: 6px;">¥<?php echo e(number_format($kpis['monthly_revenue'])); ?></h3>
                </div>
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="fa-solid fa-yen-sign"></i>
                </div>
            </div>
            <p style="font-size: 11px; color: var(--text-muted); margin-top: 14px;">
                ADR: <strong>¥<?php echo e(number_format($kpis['adr'])); ?></strong> | RevPAR: <strong>¥<?php echo e(number_format($kpis['revpar'])); ?></strong>
            </p>
        </div>
    </div>
</div>

<!-- Two-Column Operational Layout -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Today's Expected Arrivals -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3><i class="fa-solid fa-door-open text-primary"></i> <?php echo e(__('clearstay.kpis.today_arrivals')); ?></h3>
            </div>
            <a href="<?php echo e(route('reservations.index', ['status' => 'confirmed'])); ?>" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Nights</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $todayArrivals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $res): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <a href="javascript:void(0)" onclick="openReservationModal(<?php echo e($res->id); ?>)" style="color: var(--primary); font-weight: 700; text-decoration: none;">
                                    <?php echo e($res->confirmation_code); ?>

                                </a>
                            </td>
                            <td>
                                <strong><?php echo e($res->guest->full_name); ?></strong>
                                <div style="font-size: 11px; color: var(--text-muted);"><?php echo e($res->guest->phone ?? 'No phone'); ?></div>
                            </td>
                            <td><span class="badge badge-inspecting">Room <?php echo e($res->room->room_number); ?></span></td>
                            <td><?php echo e($res->nights); ?> nts</td>
                            <td>
                                <form action="<?php echo e(route('reservations.check_in', $res->id)); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-success btn-sm" style="padding: 4px 10px;">
                                        <i class="fa-solid fa-key"></i> Check In
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                <i class="fa-solid fa-calendar-check" style="font-size: 24px; opacity: 0.4;"></i>
                                <div style="margin-top: 6px;">No pending arrivals scheduled for today.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Today's Expected Departures -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3><i class="fa-solid fa-door-closed" style="color: #ef4444;"></i> <?php echo e(__('clearstay.kpis.today_departures')); ?></h3>
            </div>
            <a href="<?php echo e(route('reservations.index', ['status' => 'checked_in'])); ?>" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Balance</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $todayDepartures; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $res): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <a href="javascript:void(0)" onclick="openReservationModal(<?php echo e($res->id); ?>)" style="color: var(--primary); font-weight: 700; text-decoration: none;">
                                    <?php echo e($res->confirmation_code); ?>

                                </a>
                            </td>
                            <td>
                                <strong><?php echo e($res->guest->full_name); ?></strong>
                            </td>
                            <td><span class="badge badge-occupied">Room <?php echo e($res->room->room_number); ?></span></td>
                            <td>¥<?php echo e(number_format($res->total_price)); ?></td>
                            <td>
                                <form action="<?php echo e(route('reservations.check_out', $res->id)); ?>" method="POST">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="btn btn-secondary btn-sm" style="padding: 4px 10px;">
                                        <i class="fa-solid fa-receipt"></i> Check Out
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                <i class="fa-solid fa-bell-concierge" style="font-size: 24px; opacity: 0.4;"></i>
                                <div style="margin-top: 6px;">No guest check-outs remaining today.</div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Housekeeping Urgent Tasks & Audit Logs -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 24px;">
    <!-- Housekeeping Tasks -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-broom text-primary"></i> Pending Housekeeping Tasks</h3>
            <a href="<?php echo e(route('housekeeping.index')); ?>" class="btn btn-secondary btn-sm">Go to Cleaning Board</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $urgentTasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $task): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><strong>Room <?php echo e($task->room->room_number); ?></strong></td>
                            <td><?php echo e(ucfirst(str_replace('_', ' ', $task->task_type))); ?></td>
                            <td>
                                <span class="badge badge-<?php echo e($task->priority === 'urgent' ? 'dirty' : 'inspecting'); ?>">
                                    <?php echo e(strtoupper($task->priority)); ?>

                                </span>
                            </td>
                            <td><span class="badge badge-<?php echo e($task->status); ?>"><?php echo e(strtoupper($task->status)); ?></span></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                All rooms inspected and clear!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Security & Audit Trail -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-shield-halved" style="color: #059669;"></i> Real-Time Audit & Security Trail</h3>
        </div>
        <div style="padding: 16px;">
            <ul style="list-style: none;">
                <?php $__currentLoopData = $recentLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li style="padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px; display: flex; justify-content: space-between;">
                        <div>
                            <strong><?php echo e($log->action); ?></strong>
                            <div style="font-size: 11px; color: var(--text-muted);">
                                By: <?php echo e($log->user ? $log->user->name : 'System'); ?> | IP: <?php echo e($log->ip_address); ?>

                            </div>
                        </div>
                        <div style="font-size: 11px; color: var(--text-muted);">
                            <?php echo e($log->created_at->diffForHumans()); ?>

                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/dashboard/index.blade.php ENDPATH**/ ?>