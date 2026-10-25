<?php $__env->startSection('title', __('clearstay.reservations.title')); ?>
<?php $__env->startSection('header_title', __('clearstay.reservations.title')); ?>
<?php $__env->startSection('header_subtitle', 'Multi-room bookings, serialized row-locked date modifications, and guest check-ins'); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 14px;">
        <form action="<?php echo e(route('reservations.index')); ?>" method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; flex: 1;">
            <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search Code, Guest Name, Room..." 
                style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; min-width: 240px;">

            <select name="status" style="padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">
                <option value=""><?php echo e(__('clearstay.board.all_statuses')); ?></option>
                <option value="confirmed" <?php echo e($status == 'confirmed' ? 'selected' : ''); ?>>Confirmed</option>
                <option value="checked_in" <?php echo e($status == 'checked_in' ? 'selected' : ''); ?>>Checked In</option>
                <option value="checked_out" <?php echo e($status == 'checked_out' ? 'selected' : ''); ?>>Checked Out</option>
                <option value="cancelled" <?php echo e($status == 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
            </select>

            <input type="date" name="date" value="<?php echo e($date); ?>" style="padding: 7px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px;">

            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-magnifying-glass"></i> Filter
            </button>
            <?php if($search || $status || $date): ?>
                <a href="<?php echo e(route('reservations.index')); ?>" class="btn btn-secondary btn-sm" title="Clear Filters">Clear</a>
            <?php endif; ?>
        </form>

        <a href="<?php echo e(route('reservations.create')); ?>" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> <?php echo e(__('clearstay.reservations.new_reservation')); ?>

        </a>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Guest Information</th>
                    <th>Room</th>
                    <th>Dates (Stay Duration)</th>
                    <th>Total Price</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $reservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $res): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <a href="javascript:void(0)" onclick="openReservationModal(<?php echo e($res->id); ?>)" style="color: var(--primary); font-weight: 700; text-decoration: none;">
                                <i class="fa-solid fa-eye"></i> <?php echo e($res->confirmation_code); ?>

                            </a>
                        </td>
                        <td>
                            <strong><?php echo e($res->guest->full_name); ?></strong>
                            <div style="font-size: 11px; color: var(--text-muted);">
                                <?php echo e($res->guest->email); ?> | <?php echo e($res->guest->phone ?? 'N/A'); ?>

                            </div>
                        </td>
                        <td>
                            <strong>Room <?php echo e($res->room->room_number); ?></strong>
                            <div style="font-size: 11px; color: var(--text-muted);"><?php echo e($res->room->roomType->name); ?></div>
                        </td>
                        <td>
                            <div><?php echo e($res->check_in_date->format('Y-m-d')); ?> &rarr; <?php echo e($res->check_out_date->format('Y-m-d')); ?></div>
                            <div style="font-size: 11px; color: var(--text-muted);"><?php echo e($res->nights); ?> night(s), <?php echo e($res->adults); ?> adult(s)</div>
                        </td>
                        <td>
                            <strong>¥<?php echo e(number_format($res->total_price)); ?></strong>
                            <div><span class="badge badge-inspecting" style="font-size: 9px;"><?php echo e(ucfirst($res->payment_status)); ?></span></div>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo e($res->status); ?>"><?php echo e(strtoupper(str_replace('_', ' ', $res->status))); ?></span>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 6px; align-items: center;">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="openReservationModal(<?php echo e($res->id); ?>)" title="View Details & Change Dates">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <?php if($res->status === 'confirmed'): ?>
                                    <form action="<?php echo e(route('reservations.check_in', $res->id)); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-success btn-sm" title="Check In Guest">
                                            <i class="fa-solid fa-key"></i> In
                                        </button>
                                    </form>
                                <?php elseif($res->status === 'checked_in'): ?>
                                    <form action="<?php echo e(route('reservations.check_out', $res->id)); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Check Out Guest">
                                            <i class="fa-solid fa-receipt"></i> Out
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if(in_array($res->status, ['confirmed', 'checked_in'])): ?>
                                    <form action="<?php echo e(route('reservations.cancel', $res->id)); ?>" method="POST" onsubmit="return confirm('Cancel reservation <?php echo e($res->confirmation_code); ?>?');">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-danger btn-sm" title="Cancel Booking">
                                            <i class="fa-solid fa-ban"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            <i class="fa-solid fa-calendar-xmark" style="font-size: 28px; opacity: 0.4;"></i>
                            <div style="margin-top: 8px;">No reservations found matching your criteria.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if($reservations->hasPages()): ?>
        <div style="padding: 16px 24px; border-top: 1px solid var(--border);">
            <?php echo e($reservations->links()); ?>

        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/reservations/index.blade.php ENDPATH**/ ?>