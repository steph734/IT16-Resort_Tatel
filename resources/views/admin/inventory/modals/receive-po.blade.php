<style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        overflow-y: auto;
    }
    
    .modal-content {
        background-color: white;
        margin: 5% auto;
        padding: 0;
        border-radius: 12px;
        width: 90%;
        max-width: 700px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }
    
    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #284B53;
    }
    
    .close {
        font-size: 1.5rem;
        font-weight: 300;
        color: #6b7280;
        cursor: pointer;
        border: none;
        background: none;
    }
    
    .close:hover {
        color: #1f2937;
    }
    
    .modal-body {
        padding: 1.5rem;
    }
    
    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }
    
    .receive-items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }
    
    .receive-items-table th,
    .receive-items-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .receive-items-table th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #374151;
        font-size: 0.813rem;
    }
    
    .form-control {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.875rem;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #5EC2D0;
        box-shadow: 0 0 0 3px rgba(94, 194, 208, 0.1);
    }
    
    .btn-secondary {
        background-color: #e5e7eb;
        color: #374151;
    }
    
    .btn-secondary:hover {
        background-color: #d1d5db;
    }
</style>

<!-- Receive PO Modal -->
<div id="receivePOModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Receive Stock - PO {{ $po->po_number }}</h3>
            <button class="close" onclick="closeReceivePOModal()">&times;</button>
        </div>
        <form id="receivePOForm">
            <div class="modal-body">
                <p style="margin-bottom: 1rem; color: #6b7280;">
                    Enter the quantities received for each item. This will update inventory levels.
                </p>
                
                <table class="receive-items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="width: 120px;">Ordered</th>
                            <th style="width: 120px;">Already Received</th>
                            <th style="width: 120px;">Receive Now</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($po->items as $index => $item)
                        <tr>
                            <td>
                                <div style="font-weight: 600;">{{ $item->inventoryItem->name }}</div>
                                <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]" value="{{ $item->id }}">
                            </td>
                            <td>{{ $item->quantity_ordered }}</td>
                            <td>{{ $item->quantity_received }}</td>
                            <td>
                                <input 
                                    type="number" 
                                    name="items[{{ $index }}][quantity_received]" 
                                    class="form-control" 
                                    min="0" 
                                    max="{{ $item->remaining_quantity }}" 
                                    value="{{ $item->remaining_quantity }}"
                                    placeholder="0">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 8px;">
                    <i class="fas fa-info-circle" style="color: #f59e0b;"></i>
                    <strong>Note:</strong> Receiving stock will automatically update inventory quantities and create stock movement records.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeReceivePOModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check"></i> Confirm Receipt
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('receivePOForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Build items array
    const items = [];
    let itemIndex = 0;
    while (formData.has(`items[${itemIndex}][purchase_order_item_id]`)) {
        items.push({
            purchase_order_item_id: formData.get(`items[${itemIndex}][purchase_order_item_id]`),
            quantity_received: formData.get(`items[${itemIndex}][quantity_received]`) || 0
        });
        itemIndex++;
    }
    
    const data = { items: items };
    
    try {
        const response = await fetch('/admin/inventory/purchase-orders/{{ $po->id }}/receive', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Stock received successfully!');
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to receive stock'));
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
});
</script>
