@extends('admin.layout')

@section('title', 'Configuration des Paiements')

@section('content')
<div class="content-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-gear"></i> Configuration des Paiements</h1>
    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

<!-- Messages Flash -->
@if($message = Session::get('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle"></i> {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if($message = Session::get('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle"></i> {{ $message }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<form action="{{ route('admin.payment-settings.update') }}" method="POST" id="paymentSettingsForm">
    @csrf

    <!-- Stripe Configuration -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-credit-card"></i> Configuration Stripe</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Secret Key</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="stripe_secret_key" 
                               id="stripeSecretKey" placeholder="sk_live_..." value="{{ $stripeSecretKeyMasked }}">
                        <button class="btn btn-outline-secondary" type="button" id="toggleStripeSecret">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted">Clé secrète Stripe (commence par sk_live_ ou sk_test_)</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Public Key</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="stripe_public_key" 
                               id="stripePublicKey" placeholder="pk_live_..." value="{{ $stripePublicKeyMasked }}">
                        <button class="btn btn-outline-secondary" type="button" id="toggleStripePublic">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted">Clé publique Stripe (commence par pk_live_ ou pk_test_)</small>
                </div>
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-outline-info" id="testStripeBtn">
                    <i class="bi bi-arrow-repeat"></i> Tester la connexion Stripe
                </button>
                <div id="stripeTestResult" class="mt-2"></div>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                Obtenez vos clés sur <a href="https://dashboard.stripe.com" target="_blank">dashboard.stripe.com</a>
            </div>
        </div>
    </div>

    <!-- PayPal Configuration -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-paypal"></i> Configuration PayPal</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Client ID</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="paypal_client_id" 
                               id="paypalClientId" placeholder="Af9rds..." value="{{ $paypalClientIdMasked }}">
                        <button class="btn btn-outline-secondary" type="button" id="togglePaypalClient">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted">ID Client PayPal</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Secret</label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="paypal_secret" 
                               id="paypalSecret" placeholder="ELX-hhJ1..." value="{{ $paypalSecretMasked }}">
                        <button class="btn btn-outline-secondary" type="button" id="togglePaypalSecret">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted">Clé secrète PayPal</small>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Mode</label>
                    <select class="form-select" name="paypal_mode">
                        <option value="live" {{ $paypalMode === 'live' ? 'selected' : '' }}>
                            🔴 Live (Production)
                        </option>
                        <option value="sandbox" {{ $paypalMode === 'sandbox' ? 'selected' : '' }}>
                            🟡 Sandbox (Test)
                        </option>
                    </select>
                    <small class="text-muted">Choisissez le mode de fonctionnement</small>
                </div>
            </div>

            <div class="mb-3">
                <button type="button" class="btn btn-outline-warning" id="testPaypalBtn">
                    <i class="bi bi-arrow-repeat"></i> Tester la connexion PayPal
                </button>
                <div id="paypalTestResult" class="mt-2"></div>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> 
                Obtenez vos clés sur <a href="https://developer.paypal.com" target="_blank">developer.paypal.com</a>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Enregistrer les modifications
        </button>
        <a href="{{ route('admin.payment-settings.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-clockwise"></i> Réinitialiser
        </a>
    </div>
</form>

<!-- Informations utiles -->
<div class="card mt-4">
    <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations Utiles</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>📌 Format des Clés Stripe</h6>
                <ul>
                    <li><strong>Secret Key:</strong> commence par <code>sk_live_</code> ou <code>sk_test_</code></li>
                    <li><strong>Public Key:</strong> commence par <code>pk_live_</code> ou <code>pk_test_</code></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>📌 Mode PayPal</h6>
                <ul>
                    <li><strong>Live:</strong> Paiements réels (production)</li>
                    <li><strong>Sandbox:</strong> Paiements de test</li>
                </ul>
            </div>
        </div>
        <hr>
        <p><strong>⚠️ Attention:</strong> Pour des raisons de sécurité, les clés sont masquées dans ce formulaire. Veuillez entrer la clé complète si vous souhaitez la modifier.</p>
    </div>
</div>

<style>
    .content-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
    }

    .content-header h1 {
        margin: 0;
        color: #333;
    }

    .card {
        border: 1px solid #e9ecef;
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .card-header {
        border-bottom: 1px solid #e9ecef;
    }

    code {
        background-color: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.875rem;
    }

    .input-group .btn-outline-secondary {
        cursor: pointer;
    }

    .alert {
        border-radius: 0.5rem;
        border: none;
    }
</style>

<script>
    // Toggle password visibility
    document.getElementById('toggleStripeSecret').addEventListener('click', function() {
        const input = document.getElementById('stripeSecretKey');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.innerHTML = input.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });

    document.getElementById('toggleStripePublic').addEventListener('click', function() {
        const input = document.getElementById('stripePublicKey');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.innerHTML = input.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });

    document.getElementById('togglePaypalClient').addEventListener('click', function() {
        const input = document.getElementById('paypalClientId');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.innerHTML = input.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });

    document.getElementById('togglePaypalSecret').addEventListener('click', function() {
        const input = document.getElementById('paypalSecret');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.innerHTML = input.type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
    });

    // Test Stripe
    document.getElementById('testStripeBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Test en cours...';

        fetch('{{ route("admin.payment-settings.test-stripe") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('stripeTestResult');
            if (data.status === 'success') {
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <strong>Connexion réussie!</strong><br>
                        Compte: ${data.account_id}<br>
                        Email: ${data.email}
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle"></i> <strong>Erreur:</strong><br>
                        ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('stripeTestResult').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <strong>Erreur:</strong><br>
                    ${error.message}
                </div>
            `;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Tester la connexion Stripe';
        });
    });

    // Test PayPal
    document.getElementById('testPaypalBtn').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Test en cours...';

        fetch('{{ route("admin.payment-settings.test-paypal") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('paypalTestResult');
            const alertClass = data.status === 'success' ? 'alert-success' : (data.status === 'warning' ? 'alert-warning' : 'alert-danger');
            const icon = data.status === 'success' ? 'check-circle' : 'exclamation-circle';
            resultDiv.innerHTML = `
                <div class="alert ${alertClass}">
                    <i class="bi bi-${icon}"></i> <strong>${data.status === 'success' ? 'Succès' : 'Attention'}!</strong><br>
                    ${data.message}
                </div>
            `;
        })
        .catch(error => {
            document.getElementById('paypalTestResult').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <strong>Erreur:</strong><br>
                    ${error.message}
                </div>
            `;
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Tester la connexion PayPal';
        });
    });
    document.getElementById('paymentSettingsForm').addEventListener('submit', function() {
    const fields = [
        'stripeSecretKey', 'stripePublicKey',
        'paypalClientId', 'paypalSecret'
    ];
    fields.forEach(id => {
        const input = document.getElementById(id);
        // Si la valeur contient des étoiles = non modifiée → on vide
        if (input.value.includes('*')) {
            input.value = '';
        }
    });
});
</script>
@endsection
