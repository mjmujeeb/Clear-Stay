@extends('layouts.public')

@section('meta_title', 'ClearStay 2.0 - Connected Hotel Operations & Multi-Channel Distribution')
@section('meta_description', 'One connected enterprise workspace for modern hotel operations. Central inventory, multi-channel distribution, transactional outbox and context-aware security.')

@section('content')
<!-- HERO SECTION -->
<section style="background: radial-gradient(120% 80% at 50% 0%, #F0FDF4 0%, #FFFFFF 65%); padding: 90px 0 70px; border-bottom: 1px solid var(--border); position: relative;">
    <div class="container" style="text-align: center; max-width: 960px;">
        <!-- Live Tagline Chip -->
        <div style="display: inline-flex; align-items: center; gap: 8px; background: #E6F3F2; color: #1E605B; padding: 6px 16px; border-radius: 9999px; font-size: 13px; font-weight: 700; margin-bottom: 24px; border: 1px solid #BCE3E0;">
            <span style="width: 8px; height: 8px; border-radius: 50%; background: #287F79; display: inline-block;"></span>
            Temairazu-Inspired Architecture &bull; 日本語 / English
        </div>

        <h1 style="font-size: 50px; font-weight: 900; color: #0F1D2F; line-height: 1.15; letter-spacing: -0.03em; margin-bottom: 22px;">
            One connected workspace for <span style="color: #287F79;">modern hotel operations.</span>
        </h1>

        <p style="font-size: 19px; color: #475569; margin-bottom: 40px; line-height: 1.6; max-width: 780px; margin-left: auto; margin-right: auto;">
            Manage reservations, central inventory, distribution channels, revenue and hotel operations through one integrated platform. Zero overbookings with row-locked serialization.
        </p>

        <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin-bottom: 50px;">
            <a href="#product-demo" class="btn-demo" style="padding: 14px 28px; font-size: 15px;">
                <i class="fa-solid fa-compass"></i> Explore Platform
            </a>
            <a href="{{ route('login') }}" class="btn-login-outline" style="padding: 14px 26px; font-size: 15px; font-weight: 700;">
                <i class="fa-solid fa-desktop"></i> Open Demo Workspace
            </a>
            <a href="#integrations-section" style="background: #F8FAFC; color: #183B50; padding: 14px 24px; border-radius: 8px; text-decoration: none; font-size: 15px; font-weight: 700; border: 1px solid var(--border);">
                <i class="fa-solid fa-arrows-split-up-and-left"></i> View Integrations
            </a>
        </div>

        <!-- Hero Genuine UI Preview Showcase Card -->
        <div style="background: #FFFFFF; border: 1px solid var(--border); border-radius: 18px; box-shadow: 0 25px 50px -12px rgba(15, 29, 47, 0.12); overflow: hidden; text-align: left;">
            <!-- Top Window Bar -->
            <div style="background: #0F1D2F; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #EF4444;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #F59E0B;"></span>
                    <span style="width: 10px; height: 10px; border-radius: 50%; background: #10B981;"></span>
                    <span style="color: #94A3B8; font-size: 12px; margin-left: 10px; font-family: monospace;">ClearStay 2.0 &bull; Unified Operations Engine</span>
                </div>
                <div style="display: flex; gap: 12px; font-size: 12px; color: #A5F3FC;">
                    <span><i class="fa-solid fa-circle-check" style="color: #34D399;"></i> Transactional Outbox Active</span>
                    <span style="color: #64748B;">|</span>
                    <span><i class="fa-solid fa-shield-halved" style="color: #38BDF8;"></i> Context-Aware RBAC+ABAC</span>
                </div>
            </div>

            <!-- Preview Dashboard Strip -->
            <div style="padding: 24px; background: #F8FAFC; border-bottom: 1px solid var(--border);">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Real-Time Occupancy</div>
                        <div style="font-size: 26px; font-weight: 900; color: #183B50; margin-top: 4px;">88.4%</div>
                        <div style="font-size: 12px; color: #065F46; margin-top: 4px;"><i class="fa-solid fa-arrow-trend-up"></i> +4.2% vs last week</div>
                    </div>
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Average Daily Rate (ADR)</div>
                        <div style="font-size: 26px; font-weight: 900; color: #287F79; margin-top: 4px;">¥22,800</div>
                        <div style="font-size: 12px; color: #065F46; margin-top: 4px;"><i class="fa-solid fa-check"></i> Parity Verified Across OTAs</div>
                    </div>
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Active Outbox Events</div>
                        <div style="font-size: 26px; font-weight: 900; color: #0284C7; margin-top: 4px;">100% Sync</div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">0 failed syncs &bull; 14ms latency</div>
                    </div>
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Security Posture</div>
                        <div style="font-size: 26px; font-weight: 900; color: #065F46; margin-top: 4px;">Risk 18/100</div>
                        <div style="font-size: 12px; color: #065F46; margin-top: 4px;"><i class="fa-solid fa-shield"></i> Safe Baseline Hygiene</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- INTERACTIVE PRODUCT DEMONSTRATION TOUR -->
<section id="product-demo" style="padding: 80px 0; background: #FFFFFF; border-bottom: 1px solid var(--border);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 40px;">
            <span style="font-size: 12px; font-weight: 800; color: #287F79; text-transform: uppercase; letter-spacing: 0.08em;">Interactive Product Tour</span>
            <h2 style="font-size: 34px; font-weight: 900; color: #0F1D2F; margin: 6px 0 10px;">
                Explore Core Workflows in Real Time
            </h2>
            <p style="font-size: 15px; color: var(--text-muted); max-width: 650px; margin: 0 auto;">
                Select a platform module below to interact with live system components, view synchronized data structures, and experience enterprise usability.
            </p>
        </div>

        <!-- Tour Tabs Bar -->
        <div style="display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; margin-bottom: 30px;">
            <button type="button" class="tour-btn" id="tour-btn-reservations" onclick="switchTour('reservations')" style="padding: 10px 18px; border-radius: 8px; border: 1px solid #287F79; background: #287F79; color: white; font-weight: 700; font-size: 13px; cursor: pointer;">
                <i class="fa-solid fa-calendar-check"></i> 1. Reservations
            </button>
            <button type="button" class="tour-btn" id="tour-btn-channels" onclick="switchTour('channels')" style="padding: 10px 18px; border-radius: 8px; border: 1px solid var(--border); background: #F8FAFC; color: var(--text-dark); font-weight: 700; font-size: 13px; cursor: pointer;">
                <i class="fa-solid fa-network-wired"></i> 2. Channel Distribution
            </button>
            <button type="button" class="tour-btn" id="tour-btn-inventory" onclick="switchTour('inventory')" style="padding: 10px 18px; border-radius: 8px; border: 1px solid var(--border); background: #F8FAFC; color: var(--text-dark); font-weight: 700; font-size: 13px; cursor: pointer;">
                <i class="fa-solid fa-layer-group"></i> 3. Central Inventory
            </button>
            <button type="button" class="tour-btn" id="tour-btn-analytics" onclick="switchTour('analytics')" style="padding: 10px 18px; border-radius: 8px; border: 1px solid var(--border); background: #F8FAFC; color: var(--text-dark); font-weight: 700; font-size: 13px; cursor: pointer;">
                <i class="fa-solid fa-chart-line"></i> 4. Hospitality Analytics
            </button>
            <button type="button" class="tour-btn" id="tour-btn-readiness" onclick="switchTour('readiness')" style="padding: 10px 18px; border-radius: 8px; border: 1px solid var(--border); background: #F8FAFC; color: var(--text-dark); font-weight: 700; font-size: 13px; cursor: pointer;">
                <i class="fa-solid fa-vial-circle-check"></i> 5. Partner Assurance
            </button>
            <button type="button" class="tour-btn" id="tour-btn-security" onclick="switchTour('security')" style="padding: 10px 18px; border-radius: 8px; border: 1px solid var(--border); background: #F8FAFC; color: var(--text-dark); font-weight: 700; font-size: 13px; cursor: pointer;">
                <i class="fa-solid fa-shield-halved"></i> 6. Security Governance
            </button>
        </div>

        <!-- Tour Demo Stage -->
        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 16px; padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
            <!-- TOUR 1: RESERVATIONS -->
            <div class="tour-pane" id="tour-pane-reservations">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #183B50; margin: 0;">Multi-Channel Reservation Management</h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin: 2px 0 0;">Unified intake from Rakuten, Booking.com, Agoda, and Direct Web Booking Engine</p>
                    </div>
                    <span class="badge" style="background: #D1FAE5; color: #065F46; font-weight: 700; padding: 6px 12px;">Live Intake Active</span>
                </div>
                <div style="background: white; border: 1px solid var(--border); border-radius: 10px; overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #F1F5F9; text-align: left; font-size: 11px; color: var(--text-muted); text-transform: uppercase;">
                                <th style="padding: 12px 16px;">Booking Code</th>
                                <th style="padding: 12px 10px;">Guest Name</th>
                                <th style="padding: 12px 10px;">Channel</th>
                                <th style="padding: 12px 10px;">Room Category</th>
                                <th style="padding: 12px 10px;">Check-In</th>
                                <th style="padding: 12px 10px;">Total</th>
                                <th style="padding: 12px 16px; text-align: right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 16px; font-family: monospace; font-weight: 700; color: #0284C7;">BK-TYO-9821</td>
                                <td style="padding: 12px 10px; font-weight: 700;">Kenji Sato</td>
                                <td style="padding: 12px 10px;"><span class="badge" style="background: #FEE2E2; color: #991B1B;">Rakuten Travel</span></td>
                                <td style="padding: 12px 10px;">Superior Queen Double</td>
                                <td style="padding: 12px 10px;">Today (15:00)</td>
                                <td style="padding: 12px 10px; font-weight: 700;">¥42,000</td>
                                <td style="padding: 12px 16px; text-align: right;"><span class="badge" style="background: #D1FAE5; color: #065F46;">CONFIRMED</span></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 12px 16px; font-family: monospace; font-weight: 700; color: #0284C7;">BK-TYO-9822</td>
                                <td style="padding: 12px 10px; font-weight: 700;">Emma Watson</td>
                                <td style="padding: 12px 10px;"><span class="badge" style="background: #E0E7FF; color: #3730A3;">Booking.com</span></td>
                                <td style="padding: 12px 10px;">Deluxe Executive Twin</td>
                                <td style="padding: 12px 10px;">Tomorrow</td>
                                <td style="padding: 12px 10px; font-weight: 700;">¥59,000</td>
                                <td style="padding: 12px 16px; text-align: right;"><span class="badge" style="background: #FEF3C7; color: #92400E;">CHECK-IN PENDING</span></td>
                            </tr>
                            <tr>
                                <td style="padding: 12px 16px; font-family: monospace; font-weight: 700; color: #0284C7;">BK-KYO-4410</td>
                                <td style="padding: 12px 10px; font-weight: 700;">Taro Tanaka</td>
                                <td style="padding: 12px 10px;"><span class="badge" style="background: #E6F3F2; color: #1E605B;">Direct Web</span></td>
                                <td style="padding: 12px 10px;">Presidential Tatami Suite</td>
                                <td style="padding: 12px 10px;">Nov 12</td>
                                <td style="padding: 12px 10px; font-weight: 700;">¥116,000</td>
                                <td style="padding: 12px 16px; text-align: right;"><span class="badge" style="background: #D1FAE5; color: #065F46;">PAID ONLINE</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TOUR 2: CHANNEL DISTRIBUTION -->
            <div class="tour-pane" id="tour-pane-channels" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #183B50; margin: 0;">Multi-Channel Distribution Hub</h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin: 2px 0 0;">Outbox pattern guarantees delivery to external OTAs with exponential retry backoff</p>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong>Rakuten Travel</strong>
                            <span class="badge" style="background: #D1FAE5; color: #065F46;">ONLINE</span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">
                            <div>Sync: <strong>Instant Outbox</strong></div>
                            <div>Latency: <strong>142ms</strong></div>
                            <div>Rates: <strong>100% Parity</strong></div>
                        </div>
                    </div>
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong>Booking.com</strong>
                            <span class="badge" style="background: #D1FAE5; color: #065F46;">ONLINE</span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">
                            <div>Sync: <strong>Instant Outbox</strong></div>
                            <div>Latency: <strong>185ms</strong></div>
                            <div>Rates: <strong>100% Parity</strong></div>
                        </div>
                    </div>
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong>Agoda</strong>
                            <span class="badge" style="background: #D1FAE5; color: #065F46;">ONLINE</span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">
                            <div>Sync: <strong>Instant Outbox</strong></div>
                            <div>Latency: <strong>210ms</strong></div>
                            <div>Rates: <strong>100% Parity</strong></div>
                        </div>
                    </div>
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 18px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <strong>Expedia</strong>
                            <span class="badge" style="background: #D1FAE5; color: #065F46;">ONLINE</span>
                        </div>
                        <div style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">
                            <div>Sync: <strong>Instant Outbox</strong></div>
                            <div>Latency: <strong>198ms</strong></div>
                            <div>Rates: <strong>100% Parity</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOUR 3: CENTRAL INVENTORY -->
            <div class="tour-pane" id="tour-pane-inventory" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #183B50; margin: 0;">Central Inventory &amp; Bulk Studio</h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin: 2px 0 0;">Universal availability matrix with preview diffing and instant 1-click rollback</p>
                    </div>
                </div>
                <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px; font-size: 13px;">
                    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; text-align: center;">
                        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 12px 6px;">
                            <div style="font-weight: 700; color: var(--text-muted);">Mon 09</div>
                            <div style="font-size: 16px; font-weight: 800; color: #065F46; margin: 4px 0;">8 Left</div>
                            <div style="font-size: 11px; color: var(--text-muted);">¥18,000</div>
                        </div>
                        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 12px 6px;">
                            <div style="font-weight: 700; color: var(--text-muted);">Tue 10</div>
                            <div style="font-size: 16px; font-weight: 800; color: #065F46; margin: 4px 0;">7 Left</div>
                            <div style="font-size: 11px; color: var(--text-muted);">¥18,000</div>
                        </div>
                        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 12px 6px;">
                            <div style="font-weight: 700; color: var(--text-muted);">Wed 11</div>
                            <div style="font-size: 16px; font-weight: 800; color: #065F46; margin: 4px 0;">9 Left</div>
                            <div style="font-size: 11px; color: var(--text-muted);">¥18,000</div>
                        </div>
                        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 12px 6px;">
                            <div style="font-weight: 700; color: var(--text-muted);">Thu 12</div>
                            <div style="font-size: 16px; font-weight: 800; color: #065F46; margin: 4px 0;">6 Left</div>
                            <div style="font-size: 11px; color: var(--text-muted);">¥19,500</div>
                        </div>
                        <div style="background: #FEF3C7; border: 1px solid #FCD34D; border-radius: 8px; padding: 12px 6px;">
                            <div style="font-weight: 700; color: #92400E;">Fri 13</div>
                            <div style="font-size: 16px; font-weight: 800; color: #92400E; margin: 4px 0;">3 Left</div>
                            <div style="font-size: 11px; color: #78350F; font-weight: 700;">¥24,000</div>
                        </div>
                        <div style="background: #FEE2E2; border: 1px solid #FCA5A5; border-radius: 8px; padding: 12px 6px;">
                            <div style="font-weight: 700; color: #991B1B;">Sat 14</div>
                            <div style="font-size: 16px; font-weight: 800; color: #991B1B; margin: 4px 0;">1 Left</div>
                            <div style="font-size: 11px; color: #991B1B; font-weight: 700;">¥28,500</div>
                        </div>
                        <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 8px; padding: 12px 6px;">
                            <div style="font-weight: 700; color: var(--text-muted);">Sun 15</div>
                            <div style="font-size: 16px; font-weight: 800; color: #065F46; margin: 4px 0;">5 Left</div>
                            <div style="font-size: 11px; color: var(--text-muted);">¥21,000</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOUR 4: ANALYTICS -->
            <div class="tour-pane" id="tour-pane-analytics" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #183B50; margin: 0;">Pace Metrics &amp; Revenue Analytics</h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin: 2px 0 0;">Granular breakdown of channel contribution, ADR pace, and monthly RevPAR</p>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">RevPAR (Pace)</span>
                        <div style="font-size: 24px; font-weight: 900; color: #183B50; margin-top: 4px;">¥19,200</div>
                        <div style="font-size: 12px; color: #065F46;">+8.4% above budget</div>
                    </div>
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Direct Web Share</span>
                        <div style="font-size: 24px; font-weight: 900; color: #287F79; margin-top: 4px;">34.2%</div>
                        <div style="font-size: 12px; color: #065F46;">Zero OTA commission</div>
                    </div>
                    <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 16px;">
                        <span style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Cancellation Rate</span>
                        <div style="font-size: 24px; font-weight: 900; color: #0284C7; margin-top: 4px;">3.1%</div>
                        <div style="font-size: 12px; color: #065F46;">Below Japanese industry avg</div>
                    </div>
                </div>
            </div>

            <!-- TOUR 5: PARTNER ASSURANCE -->
            <div class="tour-pane" id="tour-pane-readiness" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #183B50; margin: 0;">Partner Contract Assurance &amp; Sandbox Testing</h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin: 2px 0 0;">Rigorous scenario-driven certification ensuring partner integrations meet SLA</p>
                    </div>
                </div>
                <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <strong>TravelConnect CRS &bull; Contract v2.4</strong>
                        <span class="badge" style="background: #E0F2FE; color: #0369A1; font-weight: 800;">Internal Readiness Passed (Sandbox)</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; font-size: 12px;">
                        <div style="background: #F8FAFC; padding: 10px; border-radius: 6px;">Contract Validity: <strong>100%</strong></div>
                        <div style="background: #F8FAFC; padding: 10px; border-radius: 6px;">Idempotency Check: <strong>98%</strong></div>
                        <div style="background: #F8FAFC; padding: 10px; border-radius: 6px;">Retry Resilience: <strong>95%</strong></div>
                        <div style="background: #F8FAFC; padding: 10px; border-radius: 6px;">P95 Latency: <strong>184ms</strong></div>
                    </div>
                </div>
            </div>

            <!-- TOUR 6: SECURITY GOVERNANCE -->
            <div class="tour-pane" id="tour-pane-security" style="display: none;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; color: #183B50; margin: 0;">Context-Aware Security &amp; Access Governance</h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin: 2px 0 0;">Dynamic risk scoring (0-100), device trust evaluation, and advisory AI governance</p>
                    </div>
                </div>
                <div style="background: white; border: 1px solid var(--border); border-radius: 10px; padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <span class="badge" style="background: #FCE7F3; color: #DB2777; font-weight: 800;">Advisory Recommendation</span>
                            <span style="font-size: 12px; color: var(--text-muted);">Confidence: 94%</span>
                        </div>
                        <span class="badge" style="background: #D1FAE5; color: #065F46;">ALLOW (Evaluated)</span>
                    </div>
                    <p style="font-size: 13px; color: var(--text-dark); margin: 0 0 10px;">
                        <strong>Permission Advisory:</strong> Operational staff member holds individual override on <code>rates.bulk_edit</code>. System advises manager review.
                    </p>
                    <div style="font-size: 12px; color: var(--text-muted);">
                        Recommendations remain strictly advisory. Human managers retain full authority to accept or dismiss.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CORE CAPABILITIES (8 FEATURES) -->
<section id="features-section" style="padding: 80px 0; background: #F8FAFC; border-bottom: 1px solid var(--border);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="font-size: 12px; font-weight: 800; color: #287F79; text-transform: uppercase; letter-spacing: 0.08em;">Full-Stack Capabilities</span>
            <h2 style="font-size: 34px; font-weight: 900; color: #0F1D2F; margin: 6px 0 10px;">
                Complete Suite for Accommodation Enterprises
            </h2>
            <p style="font-size: 15px; color: var(--text-muted); max-width: 650px; margin: 0 auto;">
                Every core feature is fully implemented, backed by real database models, and demonstrable in local test runs.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 24px;">
            <!-- Feature 1 -->
            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 24px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #E6F3F2; color: #287F79; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px;">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">1. Reservation Management</h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                    Atomic reservations with row-level locks (<code>FOR UPDATE</code>). Check-in/out workflows, guest records, and real-time room dispatch.
                </p>
            </div>

            <!-- Feature 2 -->
            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 24px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #E0F2FE; color: #0284C7; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px;">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">2. Central Inventory</h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                    Universal 90-day availability grid. Date-range bulk adjustments with client-side diff preview and instant 1-click rollback.
                </p>
            </div>

            <!-- Feature 3 -->
            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 24px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px;">
                    <i class="fa-solid fa-arrows-split-up-and-left"></i>
                </div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">3. Multi-Channel Distribution</h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                    Transactional outbox pattern synchronizing Rakuten, Booking.com, Agoda, and Expedia without blocking web requests.
                </p>
            </div>

            <!-- Feature 4 -->
            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 24px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #FCE7F3; color: #DB2777; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px;">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">4. Rate Management Studio</h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                    Seasonal pricing, Japanese national holiday multipliers, weekend surge rates, and rate parity parity monitoring.
                </p>
            </div>

            <!-- Feature 5 -->
            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 24px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #EDE9FE; color: #7C3AED; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px;">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">5. Booking Analytics</h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                    Real-time ADR, RevPAR, channel contribution, pickup pace, and historical snapshot comparisons powered by SQL aggregates.
                </p>
            </div>

            <!-- Feature 6 -->
            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 24px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #D1FAE5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px;">
                    <i class="fa-solid fa-vial-circle-check"></i>
                </div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">6. Partner Integration Testing</h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                    Automated contract validation, EventTrace latency tracking, and readiness reports for OTA and CRS partner certification.
                </p>
            </div>

            <!-- Feature 7 -->
            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 24px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #E0E7FF; color: #3730A3; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px;">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">7. Payment Reconciliation</h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                    OTA commission auditing, payout discrepancies, and invoice reconciliation ensuring exact financial balances.
                </p>
            </div>

            <!-- Feature 8 -->
            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 24px;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background: #F1F5F9; color: #183B50; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-dark); margin-bottom: 8px;">8. Security &amp; Access Governance</h3>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.6;">
                    Context-aware dynamic risk scoring, device trust verification, employee permission management, and immutable audit logs.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- DISTRIBUTION PIPELINE WORKFLOW SECTION -->
<section id="workflow-section" style="padding: 80px 0; background: #FFFFFF; border-bottom: 1px solid var(--border);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="font-size: 12px; font-weight: 800; color: #287F79; text-transform: uppercase; letter-spacing: 0.08em;">End-to-End Reliability</span>
            <h2 style="font-size: 34px; font-weight: 900; color: #0F1D2F; margin: 6px 0 10px;">
                How ClearStay 2.0 Synchronizes Your Hotel
            </h2>
            <p style="font-size: 15px; color: var(--text-muted); max-width: 650px; margin: 0 auto;">
                From the moment a traveler books on an OTA to revenue analytics, every step is atomic, reliable, and auditable.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; position: relative;">
            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 22px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #287F79; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-weight: 800;">1</div>
                <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px;">Reservation Received</h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">Booking ingested via REST webhook or Direct Web Engine with idempotency key.</p>
            </div>

            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 22px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #287F79; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-weight: 800;">2</div>
                <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px;">Inventory Locked</h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">Database executes <code>SELECT ... FOR UPDATE</code>. Units decreased with zero race condition.</p>
            </div>

            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 22px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #287F79; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-weight: 800;">3</div>
                <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px;">Outbox Dispatched</h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">Transactional outbox queues synchronization payloads to all connected channels.</p>
            </div>

            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 22px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #287F79; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-weight: 800;">4</div>
                <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px;">Channel Parity Confirmed</h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">All OTAs return HTTP 200 acknowledgments. EventTrace records round-trip telemetry.</p>
            </div>

            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 22px; text-align: center;">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: #287F79; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-weight: 800;">5</div>
                <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px;">Revenue Analysed</h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">Booking pace updates ADR &amp; RevPAR KPIs on the managerial executive overview.</p>
            </div>
        </div>
    </div>
</section>

<!-- INTEGRATIONS SHOWCASE SECTION -->
<section id="integrations-section" style="padding: 80px 0; background: #F8FAFC; border-bottom: 1px solid var(--border);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="font-size: 12px; font-weight: 800; color: #287F79; text-transform: uppercase; letter-spacing: 0.08em;">Hospitality Ecosystem</span>
            <h2 style="font-size: 34px; font-weight: 900; color: #0F1D2F; margin: 6px 0 10px;">
                Connected Channels &amp; Certified Adapters
            </h2>
            <p style="font-size: 15px; color: var(--text-muted); max-width: 650px; margin: 0 auto;">
                ClearStay integrates with domestic Japanese OTAs, global booking giants, and PMS/CRS platforms.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h4 style="font-size: 16px; font-weight: 800; margin: 0;">Rakuten Travel</h4>
                    <span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 11px;">Sandbox Simulator</span>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                    Japan's premier domestic travel platform. Supports XML/JSON booking dispatch, room mapping, and plan synchronization.
                </p>
                <div style="font-size: 12px; color: #065F46; font-weight: 700;"><i class="fa-solid fa-check"></i> 100% Rate &amp; Inventory Sync</div>
            </div>

            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h4 style="font-size: 16px; font-weight: 800; margin: 0;">Booking.com</h4>
                    <span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 11px;">Sandbox Simulator</span>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                    Global distribution leader. Real-time availability feeds, automated room type reconciliation, and guest messaging.
                </p>
                <div style="font-size: 12px; color: #065F46; font-weight: 700;"><i class="fa-solid fa-check"></i> Outbox Certified</div>
            </div>

            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h4 style="font-size: 16px; font-weight: 800; margin: 0;">Agoda</h4>
                    <span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 11px;">Sandbox Simulator</span>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                    Dominant across APAC markets. High-frequency price and inventory synchronization with rate plan constraints.
                </p>
                <div style="font-size: 12px; color: #065F46; font-weight: 700;"><i class="fa-solid fa-check"></i> Latency SLA Met (&lt;250ms)</div>
            </div>

            <div style="background: white; border: 1px solid var(--border); border-radius: 12px; padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h4 style="font-size: 16px; font-weight: 800; margin: 0;">Direct Web Engine</h4>
                    <span class="badge" style="background: #D1FAE5; color: #065F46; font-size: 11px;">Production Native</span>
                </div>
                <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
                    Zero-commission direct booking portal. Supports Japanese and English guests with instant booking confirmation.
                </p>
                <div style="font-size: 12px; color: #065F46; font-weight: 700;"><i class="fa-solid fa-check"></i> Commission Free (0%)</div>
            </div>
        </div>
    </div>
</section>

<!-- SECURITY & GOVERNANCE SECTION -->
<section id="security-section" style="padding: 80px 0; background: #FFFFFF; border-bottom: 1px solid var(--border);">
    <div class="container">
        <div style="text-align: center; margin-bottom: 50px;">
            <span style="font-size: 12px; font-weight: 800; color: #287F79; text-transform: uppercase; letter-spacing: 0.08em;">Enterprise Protection</span>
            <h2 style="font-size: 34px; font-weight: 900; color: #0F1D2F; margin: 6px 0 10px;">
                Intelligent Access Governance &amp; Data Security
            </h2>
            <p style="font-size: 15px; color: var(--text-muted); max-width: 650px; margin: 0 auto;">
                Multi-layer authorization designed to protect hotel financial records, guest privacy, and operational staff workflows.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 22px;">
                <i class="fa-solid fa-user-shield" style="font-size: 26px; color: #287F79; margin-bottom: 12px;"></i>
                <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px;">Hybrid RBAC + ABAC</h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">Role defaults with granular, manager-controlled individual permission grants and revocations.</p>
            </div>

            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 22px;">
                <i class="fa-solid fa-laptop-code" style="font-size: 26px; color: #0284C7; margin-bottom: 12px;"></i>
                <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px;">Device Trust Verification</h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">Tracks frontdesk terminals and manager workstations with device fingerprinting and approval queues.</p>
            </div>

            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 22px;">
                <i class="fa-solid fa-chart-line" style="font-size: 26px; color: #E8A64A; margin-bottom: 12px;"></i>
                <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px;">Dynamic Risk Scoring (0-100)</h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">Context-aware real-time threat evaluation analyzing off-hours logins and rapid cancellation spikes.</p>
            </div>

            <div style="background: #F8FAFC; border: 1px solid var(--border); border-radius: 12px; padding: 22px;">
                <i class="fa-solid fa-clock-rotate-left" style="font-size: 26px; color: #7C3AED; margin-bottom: 12px;"></i>
                <h4 style="font-size: 15px; font-weight: 800; margin-bottom: 6px;">Immutable Audit History</h4>
                <p style="font-size: 12px; color: var(--text-muted); line-height: 1.6;">Every rate update, permission change, and login event is logged with IP and before-and-after states.</p>
            </div>
        </div>
    </div>
</section>

<!-- CALL TO ACTION BANNER -->
<section style="background: linear-gradient(135deg, #183B50, #0F1D2F); color: white; padding: 80px 0; text-align: center;">
    <div class="container" style="max-width: 800px;">
        <h2 style="font-size: 36px; font-weight: 900; color: #FFFFFF; margin-bottom: 16px;">
            Ready to experience ClearStay 2.0?
        </h2>
        <p style="font-size: 16px; color: #94A3B8; margin-bottom: 34px; line-height: 1.6;">
            Launch the live demonstration console immediately. Explore multi-channel distribution, central inventory, room boards, and intelligent security governance.
        </p>
        <div style="display: flex; justify-content: center; gap: 14px; flex-wrap: wrap;">
            <a href="{{ route('login') }}" class="btn-demo" style="padding: 14px 30px; font-size: 15px;">
                <i class="fa-solid fa-play"></i> Open Demo Workspace
            </a>
            <a href="{{ route('public.contact') }}" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.25); padding: 14px 26px; border-radius: 8px; text-decoration: none; font-size: 15px; font-weight: 700;">
                <i class="fa-solid fa-envelope"></i> Request Consultation
            </a>
        </div>
    </div>
</section>

<!-- PRODUCT TOUR JAVASCRIPT -->
<script>
function switchTour(tabId) {
    document.querySelectorAll('.tour-btn').forEach(btn => {
        btn.style.background = '#F8FAFC';
        btn.style.borderColor = 'var(--border)';
        btn.style.color = 'var(--text-dark)';
    });
    document.querySelectorAll('.tour-pane').forEach(pane => {
        pane.style.display = 'none';
    });

    const activeBtn = document.getElementById('tour-btn-' + tabId);
    const activePane = document.getElementById('tour-pane-' + tabId);

    if (activeBtn) {
        activeBtn.style.background = '#287F79';
        activeBtn.style.borderColor = '#287F79';
        activeBtn.style.color = '#FFFFFF';
    }
    if (activePane) {
        activePane.style.display = 'block';
    }
}
</script>
@endsection
