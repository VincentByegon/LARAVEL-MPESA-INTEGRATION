<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-Pesa Payments — Live Dashboard</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --green:     #00C853;
            --green-dim: #00a844;
            --green-glow:#00C85340;
            --bg:        #060d0f;
            --surface:   #0c1a1e;
            --surface2:  #112028;
            --border:    #1a3040;
            --text:      #e8f4f0;
            --muted:     #5a8090;
            --accent:    #00e5ff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Space Grotesk', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── Header ── */
        .header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface);
        }
        .logo {
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .logo-icon {
            width: 40px; height: 40px;
            background: var(--green);
            border-radius: 10px;
            display: grid; place-items: center;
            font-size: 1.2rem;
        }
        .logo-text h1 { font-size: 1.2rem; font-weight: 700; }
        .logo-text p  { font-size: .75rem; color: var(--muted); }

        .live-badge {
            display: flex; align-items: center; gap: .5rem;
            background: #0a2010;
            border: 1px solid var(--green-dim);
            padding: .4rem .9rem;
            border-radius: 999px;
            font-size: .8rem;
            color: var(--green);
            font-weight: 600;
        }
        .live-dot {
            width: 8px; height: 8px;
            background: var(--green);
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .5; transform: scale(.85); }
        }

        /* ── Layout ── */
        .main { padding: 2rem; max-width: 1400px; margin: 0 auto; }

        /* ── Stats grid ── */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.4rem 1.6rem;
            position: relative;
            overflow: hidden;
            transition: border-color .2s;
        }
        .stat-card:hover { border-color: var(--green-dim); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--green), var(--accent));
            opacity: 0;
            transition: opacity .2s;
        }
        .stat-card:hover::before { opacity: 1; }
        .stat-label { font-size: .75rem; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: .5rem; }
        .stat-value {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.9rem;
            font-weight: 700;
            color: var(--green);
            line-height: 1;
        }
        .stat-value span { font-size: 1rem; color: var(--muted); margin-right: .2rem; }
        .stat-sub { font-size: .8rem; color: var(--muted); margin-top: .4rem; }

        /* ── Table ── */
        .table-wrap {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
        }
        .table-header {
            padding: 1.2rem 1.6rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .table-header h2 { font-size: 1rem; font-weight: 600; }
        .refresh-btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--muted);
            padding: .4rem .9rem;
            border-radius: 8px;
            font-size: .8rem;
            cursor: pointer;
            font-family: inherit;
            transition: all .2s;
        }
        .refresh-btn:hover { border-color: var(--green-dim); color: var(--green); }

        table { width: 100%; border-collapse: collapse; }
        thead th {
            padding: .9rem 1.4rem;
            text-align: left;
            font-size: .72rem;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .07em;
            border-bottom: 1px solid var(--border);
        }
        tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background .15s;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: var(--surface2); }
        tbody tr.new-payment {
            animation: highlight 2s ease-out;
        }
        @keyframes highlight {
            0%   { background: var(--green-glow); }
            100% { background: transparent; }
        }
        td {
            padding: 1rem 1.4rem;
            font-size: .88rem;
        }

        .customer-cell { display: flex; align-items: center; gap: .75rem; }
        .avatar {
            width: 36px; height: 36px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 50%;
            display: grid; place-items: center;
            font-weight: 700;
            font-size: .8rem;
            color: var(--green);
            flex-shrink: 0;
        }
        .customer-name { font-weight: 600; color: var(--text); }
        .customer-phone { font-size: .75rem; color: var(--muted); margin-top: .1rem; font-family: 'JetBrains Mono', monospace; }

        .amount {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
            color: var(--green);
            font-size: .95rem;
        }
        .amount .ksh { font-size: .7rem; color: var(--muted); margin-right: .1rem; }

        .trans-id {
            font-family: 'JetBrains Mono', monospace;
            font-size: .75rem;
            color: var(--muted);
        }

        .badge {
            display: inline-block;
            padding: .25rem .7rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .badge-success { background: #0a2010; color: var(--green); border: 1px solid var(--green-dim); }

        .time-cell { font-size: .8rem; color: var(--muted); }

        /* ── Empty ── */
        .empty {
            padding: 4rem;
            text-align: center;
            color: var(--muted);
        }
        .empty h3 { font-size: 1.1rem; margin-bottom: .5rem; }
        .empty p { font-size: .85rem; }

        /* ── Pagination ── */
        .pagination-wrap {
            padding: 1rem 1.6rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
        }
        .pagination-wrap .pagination { display: flex; gap: .4rem; list-style: none; }
        .pagination li a, .pagination li span {
            display: block;
            padding: .4rem .75rem;
            border: 1px solid var(--border);
            border-radius: 7px;
            font-size: .8rem;
            color: var(--muted);
            text-decoration: none;
            transition: all .2s;
        }
        .pagination li a:hover { border-color: var(--green-dim); color: var(--green); }
        .pagination li.active span { border-color: var(--green); color: var(--green); background: #0a2010; }

        /* ── Toast ── */
        #toast-container {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: .6rem;
        }
        .toast {
            background: var(--surface);
            border: 1px solid var(--green-dim);
            border-left: 4px solid var(--green);
            border-radius: 10px;
            padding: 1rem 1.3rem;
            min-width: 280px;
            max-width: 360px;
            box-shadow: 0 8px 32px rgba(0,0,0,.5);
            animation: slideIn .3s ease-out;
        }
        .toast-name { font-weight: 700; margin-bottom: .2rem; }
        .toast-amount { font-family: 'JetBrains Mono', monospace; color: var(--green); font-size: 1.1rem; }
        .toast-meta { font-size: .75rem; color: var(--muted); margin-top: .2rem; }
        @keyframes slideIn {
            from { transform: translateX(120%); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="logo">
        <div class="logo-icon">🟢</div>
        <div class="logo-text">
            <h1>M-Pesa Payments</h1>
            <p>Lipa na M-Pesa • Till {{ config('mpesa.shortcode') }}</p>
        </div>
    </div>
    <div class="live-badge">
        <div class="live-dot"></div>
        LIVE
    </div>
</header>

<main class="main">

    <!-- Stats -->
    <div class="stats" id="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Today's Revenue</div>
            <div class="stat-value" id="stat-today-total"><span>KSh</span>{{ number_format($todayTotal, 2) }}</div>
            <div class="stat-sub" id="stat-today-count">{{ $todayCount }} transaction{{ $todayCount != 1 ? 's' : '' }} today</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">All-Time Revenue</div>
            <div class="stat-value"><span>KSh</span>{{ number_format($allTimeTotal, 2) }}</div>
            <div class="stat-sub">{{ $allTimeCount }} total transactions</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Last Payment</div>
            <div class="stat-value" style="font-size:1.2rem; color: var(--text);" id="stat-last-name">
                @if($recentPayment) {{ $recentPayment->customer_name }} @else — @endif
            </div>
            <div class="stat-sub" id="stat-last-time">
                @if($recentPayment) {{ $recentPayment->created_at->diffForHumans() }} @else No payments yet @endif
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="table-wrap">
        <div class="table-header">
            <h2>💳 Payment Transactions</h2>
            <button class="refresh-btn" onclick="fetchLatest()">↻ Refresh</button>
        </div>

        <div id="table-container">
            @if($payments->count())
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Transaction ID</th>
                        <th>Status</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody id="payments-tbody">
                    @foreach($payments as $payment)
                    <tr data-id="{{ $payment->id }}">
                        <td>
                            <div class="customer-cell">
                                <div class="avatar">{{ strtoupper(substr($payment->first_name ?? '?', 0, 1)) }}</div>
                                <div>
                                    <div class="customer-name">{{ $payment->customer_name }}</div>
                                    <div class="customer-phone">{{ $payment->formatted_phone }}</div>
                                </div>
                            </div>
                        </td>
                        <td><div class="amount"><span class="ksh">KSh</span>{{ number_format($payment->trans_amount, 2) }}</div></td>
                        <td><div class="trans-id">{{ $payment->trans_id }}</div></td>
                        <td><span class="badge badge-success">{{ ucfirst($payment->status) }}</span></td>
                        <td><div class="time-cell">{{ $payment->created_at->diffForHumans() }}</div></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="empty">
                <h3>Waiting for payments…</h3>
                <p>Payments will appear here instantly when a customer pays via Lipa na M-Pesa</p>
            </div>
            @endif
        </div>

        @if($payments->hasPages())
        <div class="pagination-wrap">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</main>

<!-- Toast notifications -->
<div id="toast-container"></div>

<script>
    let lastKnownId = {{ $payments->first()?->id ?? 0 }};

    function showToast(payment) {
        const el = document.createElement('div');
        el.className = 'toast';
        el.innerHTML = `
            <div class="toast-name">💚 ${payment.customer_name}</div>
            <div class="toast-amount">KSh ${payment.amount}</div>
            <div class="toast-meta">${payment.phone} • ${payment.trans_id}</div>
        `;
        document.getElementById('toast-container').appendChild(el);
        setTimeout(() => el.remove(), 6000);
    }

    function prependRow(p) {
        const tbody = document.getElementById('payments-tbody');
        if (!tbody) {
            // If table didn't exist yet, reload page
            location.reload(); return;
        }
        const initial = p.customer_name.charAt(0).toUpperCase();
        const tr = document.createElement('tr');
        tr.dataset.id = p.id;
        tr.className = 'new-payment';
        tr.innerHTML = `
            <td>
                <div class="customer-cell">
                    <div class="avatar">${initial}</div>
                    <div>
                        <div class="customer-name">${p.customer_name}</div>
                        <div class="customer-phone">${p.phone}</div>
                    </div>
                </div>
            </td>
            <td><div class="amount"><span class="ksh">KSh</span>${p.amount}</div></td>
            <td><div class="trans-id">${p.trans_id}</div></td>
            <td><span class="badge badge-success">Completed</span></td>
            <td><div class="time-cell">just now</div></td>
        `;
        tbody.insertBefore(tr, tbody.firstChild);
    }

    function updateStats(stats) {
        const todayEl = document.getElementById('stat-today-total');
        if (todayEl) todayEl.innerHTML = `<span>KSh</span>${stats.today_total}`;
        const countEl = document.getElementById('stat-today-count');
        if (countEl) countEl.textContent = stats.today_count + ' transaction' + (stats.today_count != 1 ? 's' : '') + ' today';
    }

    async function fetchLatest() {
        try {
            const res = await fetch('/api/mpesa/latest');
            const data = await res.json();

            // Find new payments
            const newPayments = data.payments.filter(p => p.id > lastKnownId);

            if (newPayments.length > 0) {
                // Sort ascending so we prepend in correct order
                newPayments.sort((a, b) => a.id - b.id);
                newPayments.forEach(p => {
                    prependRow(p);
                    showToast(p);
                });
                lastKnownId = newPayments[newPayments.length - 1].id;

                // Update last payment stat
                const latest = newPayments[newPayments.length - 1];
                const nameEl = document.getElementById('stat-last-name');
                if (nameEl) nameEl.textContent = latest.customer_name;
                const timeEl = document.getElementById('stat-last-time');
                if (timeEl) timeEl.textContent = 'just now';
            }

            updateStats(data.stats);
        } catch (e) {
            console.error('Poll failed', e);
        }
    }

    // Poll every 5 seconds for new payments
    setInterval(fetchLatest, 5000);
</script>

</body>
</html>