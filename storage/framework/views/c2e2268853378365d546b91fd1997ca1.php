<?php $__env->startSection('meta_title', 'Contact & Property Support - ClearStay'); ?>
<?php $__env->startSection('meta_description', 'Contact ClearStay hospitality operations team and property managers across Tokyo, Kyoto, and Osaka.'); ?>

<?php $__env->startSection('content'); ?>
<section style="background: #f8fafc; padding: 60px 0 40px; border-bottom: 1px solid var(--border);">
    <div class="container" style="text-align: center; max-width: 700px;">
        <h1 style="font-size: 36px; font-weight: 800; color: var(--text-dark);">Contact & Support</h1>
        <p style="font-size: 15px; color: var(--text-muted); margin-top: 8px;">Front desk concierge and property management contact points</p>
    </div>
</section>

<div class="container" style="padding: 60px 24px; max-width: 900px;">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px;">
        <div>
            <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 16px;">Property Inquiries</h3>
            <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin-bottom: 24px;">
                For guest reservations, corporate group bookings, or property inquiries, please reach out directly to the respective hotel front desk.
            </p>

            <?php $__currentLoopData = $hotels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div style="background: #f8fafc; border: 1px solid var(--border); border-radius: 10px; padding: 16px; margin-bottom: 16px;">
                    <h4 style="font-size: 15px; font-weight: 700; color: var(--primary);"><?php echo e($h->name); ?></h4>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><?php echo e($h->address); ?>, <?php echo e($h->city); ?></p>
                    <p style="font-size: 12px; margin-top: 6px;"><i class="fa-solid fa-phone text-primary"></i> <?php echo e($h->phone ?? '+81-3-1234-5678'); ?></p>
                    <p style="font-size: 12px;"><i class="fa-solid fa-envelope text-primary"></i> <?php echo e($h->email ?? 'info@clearstay.jp'); ?></p>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div style="background: white; border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);">
            <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 16px;">Send Message to Desk</h3>
            
            <form onsubmit="event.preventDefault(); alert('Thank you. Your message has been sent to the front desk.'); this.reset();">
                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Your Name</label>
                    <input type="text" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Email Address</label>
                    <input type="email" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Select Hotel</label>
                    <select style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
                        <?php $__currentLoopData = $hotels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $h): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($h->id); ?>"><?php echo e($h->name); ?> (<?php echo e($h->city); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Message</label>
                    <textarea rows="4" required style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fa-solid fa-paper-plane"></i> Send Invalidation Inquiry
                </button>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/public/contact.blade.php ENDPATH**/ ?>