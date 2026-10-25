<?php $__env->startSection('title', __('clearstay.housekeeping.title')); ?>
<?php $__env->startSection('header_title', __('clearstay.housekeeping.title')); ?>
<?php $__env->startSection('header_subtitle', __('clearstay.housekeeping.subtitle')); ?>

<?php $__env->startSection('content'); ?>
<!-- Housekeeping Summary Badges -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 16px; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Pending Tasks</span>
                <h3 style="font-size: 24px; font-weight: 800; color: #d97706; margin-top: 4px;"><?php echo e($summary['pending']); ?></h3>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 8px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-hourglass-start"></i>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 16px; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">In Progress</span>
                <h3 style="font-size: 24px; font-weight: 800; color: var(--primary); margin-top: 4px;"><?php echo e($summary['in_progress']); ?></h3>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 8px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 16px; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Completed Today</span>
                <h3 style="font-size: 24px; font-weight: 800; color: #059669; margin-top: 4px;"><?php echo e($summary['completed_today']); ?></h3>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 8px; background: #d1fae5; color: #059669; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-check-double"></i>
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <div class="card-body" style="padding: 16px; display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700;">Dirty Rooms</span>
                <h3 style="font-size: 24px; font-weight: 800; color: #dc2626; margin-top: 4px;"><?php echo e($summary['dirty_rooms']); ?></h3>
            </div>
            <div style="width: 40px; height: 40px; border-radius: 8px; background: #fee2e2; color: #dc2626; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-spray-can-sparkles"></i>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Tasks Table -->
    <div class="card">
        <div class="card-header" style="flex-wrap: wrap; gap: 12px;">
            <h3>Active Task Queue</h3>
            <form action="<?php echo e(route('housekeeping.index')); ?>" method="GET" style="display: flex; gap: 8px;">
                <select name="status" onchange="this.form.submit()" style="padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php echo e($status == 'pending' ? 'selected' : ''); ?>>Pending</option>
                    <option value="in_progress" <?php echo e($status == 'in_progress' ? 'selected' : ''); ?>>In Progress</option>
                    <option value="completed" <?php echo e($status == 'completed' ? 'selected' : ''); ?>>Completed</option>
                </select>

                <select name="priority" onchange="this.form.submit()" style="padding: 6px 10px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px;">
                    <option value="">All Priorities</option>
                    <option value="urgent" <?php echo e($priority == 'urgent' ? 'selected' : ''); ?>>Urgent</option>
                    <option value="high" <?php echo e($priority == 'high' ? 'selected' : ''); ?>>High</option>
                    <option value="normal" <?php echo e($priority == 'normal' ? 'selected' : ''); ?>>Normal</option>
                    <option value="low" <?php echo e($priority == 'low' ? 'selected' : ''); ?>>Low</option>
                </select>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room</th>
                        <th>Task Type</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                        <th>Notes</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $tasks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <strong>Room <?php echo e($t->room->room_number); ?></strong>
                                <div style="font-size: 11px; color: var(--text-muted);">Floor <?php echo e($t->room->floor); ?>F</div>
                            </td>
                            <td><?php echo e(ucfirst(str_replace('_', ' ', $t->task_type))); ?></td>
                            <td>
                                <span class="badge badge-<?php echo e($t->priority === 'urgent' ? 'dirty' : ($t->priority === 'high' ? 'inspecting' : 'confirmed')); ?>">
                                    <?php echo e(strtoupper($t->priority)); ?>

                                </span>
                            </td>
                            <td><?php echo e($t->assignee ? $t->assignee->name : 'Unassigned'); ?></td>
                            <td style="max-width: 200px; font-size: 12px;"><?php echo e($t->notes ?? '-'); ?></td>
                            <td>
                                <span class="badge badge-<?php echo e($t->status); ?>"><?php echo e(strtoupper($t->status)); ?></span>
                            </td>
                            <td>
                                <?php if($t->status === 'pending'): ?>
                                    <form action="<?php echo e(route('housekeeping.update_status', $t->id)); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="status" value="in_progress">
                                        <button type="submit" class="btn btn-primary btn-sm" style="padding: 4px 8px;">
                                            <i class="fa-solid fa-play"></i> Start
                                        </button>
                                    </form>
                                <?php elseif($t->status === 'in_progress'): ?>
                                    <form action="<?php echo e(route('housekeeping.update_status', $t->id)); ?>" method="POST">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn btn-success btn-sm" style="padding: 4px 8px;">
                                            <i class="fa-solid fa-check"></i> Complete
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--success); font-size: 12px;"><i class="fa-solid fa-circle-check"></i> Done</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                No housekeeping tasks in this queue.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($tasks->hasPages()): ?>
            <div style="padding: 14px 20px; border-top: 1px solid var(--border);">
                <?php echo e($tasks->links()); ?>

            </div>
        <?php endif; ?>
    </div>

    <!-- Create Task Form -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-plus text-primary"></i> Dispatch New Task</h3>
        </div>
        <div class="card-body">
            <form action="<?php echo e(route('housekeeping.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Target Room</label>
                    <select name="room_id" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        <option value="">-- Choose Room --</option>
                        <?php $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rm): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($rm->id); ?>">Room <?php echo e($rm->room_number); ?> (<?php echo e(ucfirst($rm->status)); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;"><?php echo e(__('clearstay.housekeeping.task_type')); ?></label>
                    <select name="task_type" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        <option value="checkout_turnover"><?php echo e(__('clearstay.housekeeping.turnover')); ?></option>
                        <option value="daily_cleaning"><?php echo e(__('clearstay.housekeeping.daily')); ?></option>
                        <option value="deep_clean"><?php echo e(__('clearstay.housekeeping.deep_clean')); ?></option>
                        <option value="inspection"><?php echo e(__('clearstay.housekeeping.inspection')); ?></option>
                        <option value="maintenance"><?php echo e(__('clearstay.housekeeping.maintenance_type')); ?></option>
                    </select>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;"><?php echo e(__('clearstay.housekeeping.priority')); ?></label>
                    <select name="priority" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        <option value="normal"><?php echo e(__('clearstay.housekeeping.normal')); ?></option>
                        <option value="high"><?php echo e(__('clearstay.housekeeping.high')); ?></option>
                        <option value="urgent"><?php echo e(__('clearstay.housekeeping.urgent')); ?></option>
                        <option value="low"><?php echo e(__('clearstay.housekeeping.low')); ?></option>
                    </select>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;"><?php echo e(__('clearstay.housekeeping.assignee')); ?></label>
                    <select name="assigned_to" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        <option value="">-- Unassigned (Team Queue) --</option>
                        <?php $__currentLoopData = $staff; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($s->id); ?>"><?php echo e($s->name); ?> (<?php echo e(ucfirst($s->role)); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; margin-bottom: 4px;">Instructions / Notes</label>
                    <textarea name="notes" rows="3" placeholder="e.g. Broken AC remote, replace duvet cover" style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fa-solid fa-paper-plane"></i> Dispatch Task
                </button>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/housekeeping/index.blade.php ENDPATH**/ ?>