<?php $__env->startSection('meta_title', 'Rooms & Suites - ClearStay Hospitality Network'); ?>
<?php $__env->startSection('meta_description', 'Explore room types and suites across ClearStay properties in Tokyo, Kyoto, and Osaka.'); ?>

<?php $__env->startSection('content'); ?>
<section style="background: #f8fafc; padding: 60px 0 40px; border-bottom: 1px solid var(--border);">
    <div class="container" style="text-align: center;">
        <h1 style="font-size: 36px; font-weight: 800; color: var(--text-dark);">Available Rooms & Suites</h1>
        <p style="font-size: 15px; color: var(--text-muted); margin-top: 8px;">Explore modern hospitality accommodations managed via ClearStay PMS</p>
    </div>
</section>

<div class="container" style="padding: 60px 24px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 28px;">
        <?php $__currentLoopData = $roomTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div style="background: white; border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
                <div style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-size: 11px; font-weight: 700; color: #38bdf8; text-transform: uppercase;"><?php echo e($type->hotel->name); ?></span>
                        <span style="font-size: 11px; background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px;"><?php echo e($type->code); ?></span>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 800;"><?php echo e($type->name); ?></h3>
                    <div style="font-size: 22px; font-weight: 800; color: #38bdf8; margin-top: 10px;">
                        ¥<?php echo e(number_format($type->base_price)); ?> <span style="font-size: 12px; color: #94a3b8; font-weight: 400;">/ night</span>
                    </div>
                </div>

                <div style="padding: 24px; flex: 1; display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6; margin-bottom: 16px;">
                            <?php echo e($type->description ?? 'Comfortable, quiet accommodation equipped with high-speed WiFi and premium bedding.'); ?>

                        </p>

                        <div style="font-size: 13px; margin-bottom: 16px;">
                            <div style="margin-bottom: 6px;"><i class="fa-solid fa-users text-primary"></i> <strong>Capacity:</strong> Up to <?php echo e($type->capacity); ?> Guests</div>
                            <div><i class="fa-solid fa-door-open text-primary"></i> <strong>Rooms in Property:</strong> <?php echo e($type->rooms->count()); ?> Units</div>
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--border); padding-top: 16px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 12px; color: #059669; font-weight: 600;"><i class="fa-solid fa-circle-check"></i> Clean Verified</span>
                        <a href="<?php echo e(route('login')); ?>" class="btn-portal" style="font-size: 12px; padding: 6px 14px;">Staff Book</a>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/public/rooms.blade.php ENDPATH**/ ?>