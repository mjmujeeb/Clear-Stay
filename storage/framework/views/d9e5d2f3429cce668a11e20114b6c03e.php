<?php $__env->startSection('meta_title', 'Staff Sign In - ClearStay'); ?>

<?php $__env->startSection('content'); ?>
<div style="min-height: 80vh; display: flex; align-items: center; justify-content: center; padding: 40px 20px; background: #f8fafc;">
    <div style="width: 100%; max-width: 440px; background: white; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); padding: 36px;">
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #38bdf8, #0284c7); border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 22px; margin-bottom: 14px;">
                <i class="fa-solid fa-hotel"></i>
            </div>
            <h2 style="font-size: 22px; font-weight: 800; color: var(--text-dark);"><?php echo e(__('clearstay.auth.title')); ?></h2>
            <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;"><?php echo e(__('clearstay.auth.subtitle')); ?></p>
        </div>

        <?php if($errors->any()): ?>
            <div style="background: #fee2e2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #991b1b;">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div><i class="fa-solid fa-circle-exclamation"></i> <?php echo e($error); ?></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('login.post')); ?>" method="POST">
            <?php echo csrf_field(); ?>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 6px;"><?php echo e(__('clearstay.auth.email')); ?></label>
                <input type="email" name="email" value="<?php echo e(old('email', 'admin@clearstay.jp')); ?>" required autofocus
                    style="width: 100%; padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s;">
            </div>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: var(--text-dark); margin-bottom: 6px;"><?php echo e(__('clearstay.auth.password')); ?></label>
                <input type="password" name="password" value="password" required
                    style="width: 100%; padding: 11px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none;">
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; font-size: 13px;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-muted);">
                    <input type="checkbox" name="remember" checked>
                    <span><?php echo e(__('clearstay.auth.remember')); ?></span>
                </label>
                <span style="font-size: 12px; color: var(--primary); font-weight: 600;">Rate Limited (5 req/min)</span>
            </div>

            <button type="submit" style="width: 100%; background: var(--primary); color: white; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 700; border: none; cursor: pointer; transition: background 0.2s;">
                <i class="fa-solid fa-right-to-bracket"></i> <?php echo e(__('clearstay.auth.sign_in')); ?>

            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); text-align: center; font-size: 13px; color: var(--text-muted);">
            Demo Staff: <strong>admin@clearstay.jp</strong> / Password: <strong>password</strong>
            <div style="margin-top: 10px;">
                <a href="<?php echo e(route('register')); ?>" style="color: var(--primary); font-weight: 600; text-decoration: none;">Register New Staff Account &rarr;</a>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/auth/login.blade.php ENDPATH**/ ?>