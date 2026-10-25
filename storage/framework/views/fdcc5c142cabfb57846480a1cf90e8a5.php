<?php $__env->startSection('title', __('clearstay.csv.title')); ?>
<?php $__env->startSection('header_title', __('clearstay.csv.title')); ?>
<?php $__env->startSection('header_subtitle', __('clearstay.csv.subtitle')); ?>

<?php $__env->startSection('content'); ?>
<div style="max-width: 900px; margin: 0 auto;">
    <!-- Import Form Card -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fa-solid fa-file-csv text-primary"></i> Upload Bookings CSV</h3>
            <a href="<?php echo e(route('csv.template')); ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-download"></i> <?php echo e(__('clearstay.csv.download_sample')); ?>

            </a>
        </div>
        <div class="card-body">
            <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
                <?php echo e(__('clearstay.csv.instructions')); ?>

            </p>

            <form action="<?php echo e(route('csv.import')); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <div style="border: 2px dashed #cbd5e1; border-radius: 12px; padding: 32px; text-align: center; margin-bottom: 20px; background: #f8fafc;">
                    <i class="fa-solid fa-cloud-arrow-up fa-3x" style="color: var(--primary); margin-bottom: 12px;"></i>
                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 6px;">Choose your reservations CSV file</div>
                    <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">Supports .csv UTF-8 format up to 5MB</div>
                    <input type="file" name="csv_file" accept=".csv,text/csv" required style="font-size: 13px;">
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 12px;">
                    <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">
                        <i class="fa-solid fa-upload"></i> <?php echo e(__('clearstay.csv.upload_btn')); ?>

                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if(isset($result)): ?>
        <!-- Import Results Summary -->
        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h3><i class="fa-solid fa-clipboard-check text-primary"></i> <?php echo e(__('clearstay.csv.import_summary')); ?></h3>
                <div>
                    <span class="badge badge-clean"><?php echo e($result['imported']); ?> Imported</span>
                    <span class="badge badge-dirty"><?php echo e($result['failed']); ?> Failed</span>
                </div>
            </div>
            <div class="card-body">
                <?php if(!empty($result['errors'])): ?>
                    <div style="background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px; margin-bottom: 20px;">
                        <h4 style="font-size: 13px; font-weight: 700; color: #991b1b; margin-bottom: 8px;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Line-by-Line Validation Issues:
                        </h4>
                        <ul style="font-size: 12px; color: #991b1b; margin-left: 20px;">
                            <?php $__currentLoopData = $result['errors']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if(!empty($result['records'])): ?>
                    <h4 style="font-size: 13px; font-weight: 700; margin-bottom: 10px;">Successfully Registered Bookings:</h4>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Confirmation Code</th>
                                    <th>Guest</th>
                                    <th>Room</th>
                                    <th>Dates</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $result['records']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rec): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><strong style="color: var(--primary);"><?php echo e($rec['code']); ?></strong></td>
                                        <td><?php echo e($rec['guest']); ?></td>
                                        <td>Room <?php echo e($rec['room']); ?></td>
                                        <td><?php echo e($rec['dates']); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/csv/index.blade.php ENDPATH**/ ?>