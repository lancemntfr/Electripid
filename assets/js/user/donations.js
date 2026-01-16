
// Donation and PayPal functionality
function openDonationModal() {
  const modal = document.getElementById('donationModal');
  modal.style.display = 'flex';
  modal.classList.add('d-flex');
  renderPayPalButtons();
}

function closeDonationModal() {
  const modal = document.getElementById('donationModal');
  modal.style.display = 'none';
  modal.classList.remove('d-flex');
}

function selectAmount(amount, el) {
  document.getElementById('customAmount').value = '';
  selectedDonationAmount = Number(amount);

  document.querySelectorAll('.donation-btn').forEach(btn => {
    btn.classList.remove('active');
  });

  if (el) el.classList.add('active');

  renderPayPalButtons();
}

document.getElementById('customAmount').addEventListener('input', function() {
  const value = Number(this.value);

  if (value >= 1) {
    selectedDonationAmount = value;

    document.querySelectorAll('.donation-btn')
      .forEach(btn => btn.classList.remove('active'));

    renderPayPalButtons();
  }
});

function getDonationAmount() {
  const customAmountInput = document.getElementById('customAmount');
  const typedAmount = Number(customAmountInput.value);

  if (!isNaN(typedAmount) && typedAmount >= 1) {
    selectedDonationAmount = typedAmount;
    return typedAmount;
  }

  if (selectedDonationAmount && selectedDonationAmount >= 1) {
    return Number(selectedDonationAmount);
  }

  return null;
}

function renderPayPalButtons() {
  const container = document.getElementById('paypal-button-container');
  container.innerHTML = '';

  const phpAmount = getDonationAmount();

  // HARD VALIDATION
  if (!phpAmount || isNaN(phpAmount) || phpAmount < 1) {
    container.innerHTML =
      '<div class="alert alert-warning">Please enter a donation amount.</div>';
    return;
  }

  // Check if PayPal SDK is loaded
  if (typeof paypal === 'undefined') {
    container.innerHTML = '<div class="alert alert-info">Loading PayPal...</div>';
    setTimeout(function() {
      renderPayPalButtons();
    }, 500);
    return;
  }

  // Convert PHP → USD
  const usdAmount = (phpAmount / USD_RATE).toFixed(2);

  console.log('PayPal Amounts:', {
    php: phpAmount,
    usd: usdAmount
  });

  try {
    paypal.Buttons({

        // CREATE ORDER
        createOrder: async function() {
          try {
            const response = await fetch('../paypal/paypal.php?action=create', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                amount: usdAmount
              })
            });

            const text = await response.text();
            if (!response.ok) throw new Error(text);

            const result = JSON.parse(text);
            if (!result.id) throw new Error('No order ID returned');

            return result.id;

          } catch (error) {
            console.error('Create Order Error:', error);
            alert('Unable to create payment.\n\n' + error.message);
            throw error;
          }
        },

        // CAPTURE PAYMENT
        onApprove: async function(data) {
          try {
            const response = await fetch('../paypal/paypal.php?action=capture', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                orderID: data.orderID,
                phpAmount: phpAmount
              })
            });

            const text = await response.text();
            if (!response.ok) throw new Error(text);

            const result = JSON.parse(text);

            if (result.success) {
              alert(
                'Thank you for your donation of ₱' +
                phpAmount.toFixed(2) +
                ' 💚'
              );
              closeDonationModal();
              setTimeout(() => location.reload(), 1500);
            } else {
              throw new Error(result.error || 'Payment failed');
            }

          } catch (error) {
            console.error('Capture Error:', error);
            alert(
              'Payment processing failed.\n\n' +
              error.message +
              '\n\nIf charged, please contact support.'
            );
          }
        },

        // CANCEL
        onCancel: function() {
          alert('Payment was cancelled.');
        },

        // ERROR
        onError: function(err) {
          console.error('PayPal Error:', err);
          alert('An error occurred. Please try again.');
        },

        // STYLE
        style: {
          layout: 'vertical',
          color: 'blue',
          shape: 'rect',
          label: 'paypal'
        }

      }).render('#paypal-button-container')
      .catch(error => {
        console.error('Render Error:', error);
        container.innerHTML =
          '<div class="alert alert-danger">Failed to load PayPal buttons. Please refresh the page and try again.</div>';
      });
  } catch (error) {
    console.error('PayPal Initialization Error:', error);
    container.innerHTML = '<div class="alert alert-info">Loading PayPal...</div>';
    setTimeout(function() {
      renderPayPalButtons();
    }, 1000);
  }
}
