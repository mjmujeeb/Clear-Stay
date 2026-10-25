<?php $__env->startSection('meta_title', 'ClearStay - Modern Hospitality & Hotel Stay Management System'); ?>
<?php $__env->startSection('meta_description', 'ClearStay delivers multi-tenant hotel operations, real-time room dispatch, serialized row-locked date modifications, and analytics.'); ?>

<?php $__env->startSection('content'); ?>
<!-- Hero Section -->
<section style="background: linear-gradient(180deg, #f0f9ff 0%, #ffffff 100%); padding: 80px 0 60px; border-bottom: 1px solid var(--border);">
    <div class="container" style="text-align: center; max-width: 900px;">
        <span style="background: #e0f2fe; color: #0284c7; padding: 6px 16px; border-radius: 9999px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: inline-block; margin-bottom: 20px;">
            Next-Generation Hospitality PMS &bull; 日本語 / English
        </span>
        <h1 style="font-size: 46px; font-weight: 800; color: #0f172a; line-height: 1.2; letter-spacing: -0.03em; margin-bottom: 20px;">
            Seamless Hotel Operations, Real-Time Room Boards & Zero Overbookings
        </h1>
        <p style="font-size: 18px; color: #475569; margin-bottom: 36px; line-height: 1.6;">
            <?php echo e(__('clearstay.description')); ?> Built with PHP 8.3, Laravel 11/12, MySQL with Row-Level Locking (<code style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px;">SELECT ... FOR UPDATE</code>), and Chart.js.
        </p>

        <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
            <a href="<?php echo e(route('login')); ?>" class="btn-portal" style="padding: 14px 28px; font-size: 15px;">
                <i class="fa-solid fa-gauge"></i> Enter Staff Management Console
            </a>
            <a href="<?php echo e(route('public.features')); ?>" style="background: #f1f5f9; color: #0f172a; padding: 14px 24px; border-radius: 8px; text-decoration: none; font-size: 15px; font-weight: 700;">
                <i class="fa-solid fa-layer-group"></i> Architecture & Tech Stack
            </a>
        </div>
    </div>
</section>

<!-- Key Architectural Pillars -->
<section style="padding: 70px 0; background: #ffffff;">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-size: 32px; font-weight: 800; color: var(--text-dark);">Core Enterprise Capabilities</h2>
            <p style="font-size: 15px; color: var(--text-muted); margin-top: 8px;">Designed specifically to meet modern Japanese hotel and resort standards</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 28px;">
            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 14px; padding: 28px;">
                <div style="width: 48px; height: 48px; background: #e0f2fe; color: #0284c7; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 18px;">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">Row-Locked DB Transactions</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">
                    Date modifications and room allocations execute with MySQL <code style="font-weight: 600;">SELECT ... FOR UPDATE</code> to serialize concurrent requests and eliminate double-booking race conditions.
                </p>
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 14px; padding: 28px;">
                <div style="width: 48px; height: 48px; background: #d1fae5; color: #059669; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 18px;">
                    <i class="fa-solid fa-border-all"></i>
                </div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">Live Room Board (jQuery AJAX)</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">
                    Real-time floor grid with 10-second polling refresh, instant state toggles (Clean, Dirty, Inspecting), and interactive modal popups for guest check-in/out.
                </p>
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 14px; padding: 28px;">
                <div style="width: 48px; height: 48px; background: #ede9fe; color: #7c3aed; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 18px;">
                    <i class="fa-solid fa-network-wired"></i>
                </div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">Multi-Tenant Architecture</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">
                    Strict data isolation via <code style="font-weight: 600;">hotel_id</code> foreign keys and Eloquent global tenant scopes, supporting multi-property hotel chains with role permissions.
                </p>
            </div>

            <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 14px; padding: 28px;">
                <div style="width: 48px; height: 48px; background: #fef3c7; color: #d97706; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 18px;">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">Hospitality Analytics</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">
                    Interactive Chart.js visualizations for 14-day occupancy trend forecasts, RevPAR, Average Daily Rate (ADR), and monthly revenue aggregation.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Hotel Properties -->
<section style="padding: 60px 0; background: #f8fafc; border-top: 1px solid var(--border);">
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 36px;">
            <div>
                <h2 style="font-size: 28px; font-weight: 800;">Managed Hotel Properties</h2>
                <p style="font-size: 14px; color: var(--text-muted);">Multi-tenant properties running on ClearStay</p>
            </div>
            <a href="<?php echo e(route('public.rooms')); ?>" style="color: var(--primary); font-weight: 700; text-decoration: none;">View All Rooms &rarr;</a>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            <?php $__currentLoopData = $hotels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="font-size: 11px; font-weight: 700; color: var(--primary); background: var(--primary-light); padding: 3px 8px; border-radius: 6px;"><?php echo e($h->code); ?></span>
                        <span style="font-size: 12px; color: var(--text-muted);"><i class="fa-solid fa-location-dot"></i> <?php echo e($h->city); ?>, Japan</span>
                    </div>
                    <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 8px;"><?php echo e($h->name); ?></h3>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 16px;"><?php echo e($h->address); ?></p>
                    <div style="border-top: 1px solid var(--border); padding-top: 12px; display: flex; justify-content: space-between; font-size: 13px;">
                        <span><strong><?php echo e($h->total_rooms); ?></strong> Total Rooms</span>
                        <span style="color: #059669; font-weight: 600;"><i class="fa-solid fa-check"></i> Active Multi-Tenant</span>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/public/home.blade.php ENDPATH**/ ?>