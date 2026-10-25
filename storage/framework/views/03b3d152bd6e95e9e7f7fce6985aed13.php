<?php $__env->startSection('meta_title', 'Guest Concierge Portal - ClearStay'); ?>

<?php $__env->startSection('content'); ?>
<div style="background: #f8fafc; min-height: 85vh; padding: 40px 20px;">
    <div style="max-width: 680px; margin: 0 auto;">
        
        <!-- Welcome Card -->
        <div style="background: white; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.04); overflow: hidden; margin-bottom: 24px;">
            <div style="background: linear-gradient(135deg, #0284c7, #0f172a); color: white; padding: 28px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <span style="background: rgba(255,255,255,0.2); padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                            <?php echo e($reservation->hotel->name); ?>

                        </span>
                        <h2 style="font-size: 24px; font-weight: 800; margin-top: 10px;"><?php echo e(__('clearstay.guest_portal.welcome')); ?>, <?php echo e($reservation->guest->first_name); ?>!</h2>
                        <p style="font-size: 13px; opacity: 0.9; margin-top: 4px;">Confirmation: <strong><?php echo e($reservation->confirmation_code); ?></strong></p>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 12px; background: rgba(255,255,255,0.25); padding: 6px 12px; border-radius: 8px; font-weight: 700;">
                            <?php echo e(strtoupper($reservation->status)); ?>

                        </span>
                    </div>
                </div>
            </div>

            <!-- Stay Summary -->
            <div style="padding: 24px;">
                <?php if(session('success')): ?>
                    <div style="background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 8px; padding: 12px 16px; font-size: 13px; margin-bottom: 20px;">
                        <i class="fa-solid fa-circle-check"></i> <?php echo e(session('success')); ?>

                    </div>
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; background: #f8fafc; border-radius: 12px; padding: 16px; border: 1px solid var(--border);">
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Check-In Date</div>
                        <div style="font-size: 16px; font-weight: 700; color: var(--text-dark); margin-top: 2px;">
                            <?php echo e($reservation->check_in_date->format('M d, Y (D)')); ?>

                        </div>
                        <div style="font-size: 12px; color: var(--primary);">From <?php echo e(substr($reservation->hotel->check_in_time, 0, 5)); ?></div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Check-Out Date</div>
                        <div style="font-size: 16px; font-weight: 700; color: var(--text-dark); margin-top: 2px;">
                            <?php echo e($reservation->check_out_date->format('M d, Y (D)')); ?>

                        </div>
                        <div style="font-size: 12px; color: var(--primary);">Until <?php echo e(substr($reservation->hotel->check_out_time, 0, 5)); ?></div>
                    </div>
                </div>

                <div style="margin-bottom: 24px; font-size: 13px;">
                    <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 8px;">Room Details</h4>
                    <p><strong>Room <?php echo e($reservation->room->room_number); ?></strong> - <?php echo e($reservation->room->roomType->name); ?> (Floor <?php echo e($reservation->room->floor); ?>F)</p>
                    <p style="color: var(--text-muted); margin-top: 2px;">Capacity: <?php echo e($reservation->room->roomType->capacity); ?> Guests | <?php echo e($reservation->nights); ?> Night(s)</p>
                </div>

                <div style="margin-bottom: 24px; font-size: 13px;">
                    <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 8px;"><?php echo e(__('clearstay.guest_portal.hotel_information')); ?></h4>
                    <p><i class="fa-solid fa-location-dot text-primary"></i> <?php echo e($reservation->hotel->address); ?>, <?php echo e($reservation->hotel->city); ?>, Japan</p>
                    <p style="margin-top: 4px;"><i class="fa-solid fa-phone text-primary"></i> <?php echo e($reservation->hotel->phone ?? '+81-3-1234-5678'); ?></p>
                    <p style="margin-top: 6px; color: var(--text-muted); font-size: 12px;">
                        <?php echo e(__('clearstay.guest_portal.check_in_instructions', ['time' => substr($reservation->hotel->check_in_time, 0, 5)])); ?>

                    </p>
                </div>

                <!-- Amenity / Concierge Request Form -->
                <div style="border-top: 1px solid var(--border); padding-top: 24px;">
                    <h4 style="font-size: 15px; font-weight: 700; margin-bottom: 6px;">
                        <i class="fa-solid fa-bell-concierge text-primary"></i> <?php echo e(__('clearstay.guest_portal.request_amenities')); ?>

                    </h4>
                    <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 16px;">
                        Need extra amenities or want to notify us of early baggage drop? Let our team know below:
                    </p>

                    <form action="<?php echo e(route('guest.portal.request', $reservation->guest_portal_token)); ?>" method="POST">
                        <?php echo csrf_field(); ?>

                        <div style="margin-bottom: 14px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Request Category</label>
                            <select name="request_type" required style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                                <option value="extra_towels"><?php echo e(__('clearstay.guest_portal.extra_towels')); ?></option>
                                <option value="extra_pillows"><?php echo e(__('clearstay.guest_portal.extra_pillows')); ?></option>
                                <option value="cleaning_time"><?php echo e(__('clearstay.guest_portal.cleaning_time')); ?></option>
                                <option value="luggage_drop"><?php echo e(__('clearstay.guest_portal.luggage_drop')); ?></option>
                                <option value="other"><?php echo e(__('clearstay.guest_portal.other')); ?></option>
                            </select>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px;">Details or Timing Preferences</label>
                            <textarea name="details" rows="3" placeholder="e.g. Please provide 2 extra bath towels around 18:00" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px;"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px;">
                            <i class="fa-solid fa-paper-plane"></i> <?php echo e(__('clearstay.guest_portal.submit_request')); ?>

                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div style="text-align: center; font-size: 12px; color: var(--text-muted);">
            ClearStay Self-Service Guest Concierge &bull; Powered by ClearStay Hotel PMS
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.public', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\CAD Revision & Manufacturing Change Management System\ClearStay\resources\views/guest/portal.blade.php ENDPATH**/ ?>