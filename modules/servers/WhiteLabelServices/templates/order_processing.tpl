{if $orderStatus eq 'processing'}
<div class="alert alert-info">
    <h4><i class="fas fa-spinner fa-spin"></i> Your VPS is being provisioned</h4>
    <p>Please wait while we set up your VPS. This process usually takes a few minutes.</p>
    <p>Service ID: {$serviceid}</p>
    <p>Domain: {$domain}</p>
    <hr>
    <p class="text-muted">The page will automatically refresh every 30 seconds...</p>
</div>

<script>
    setTimeout(function() {
        window.location.reload();
    }, 30000);
</script>
{else}
<div class="alert alert-warning">
    <h4><i class="fas fa-exclamation-triangle"></i> Order Status: {$orderStatus}</h4>
    <p>Your order is currently being processed. Please check back later.</p>
    <p>Service ID: {$serviceid}</p>
    <p>Domain: {$domain}</p>
</div>
{/if} 