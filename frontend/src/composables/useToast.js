/**
 * Composable useToast para notificaciones
 */

export const useToast = () => {
  const showToast = (message, type = 'info', duration = 3000) => {
    // Crear elemento toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;

    // Estilos CSS
    const styleEl = document.createElement('style');
    if (!document.querySelector('style[data-toast="true"]')) {
      styleEl.setAttribute('data-toast', 'true');
      styleEl.textContent = `
        .toast {
          position: fixed;
          bottom: 20px;
          right: 20px;
          padding: 1rem 1.5rem;
          border-radius: 8px;
          color: white;
          font-weight: 600;
          z-index: 9999;
          animation: slideIn 0.3s ease-out;
          max-width: 400px;
          word-wrap: break-word;
        }

        @keyframes slideIn {
          from {
            transform: translateX(400px);
            opacity: 0;
          }
          to {
            transform: translateX(0);
            opacity: 1;
          }
        }

        @keyframes slideOut {
          from {
            transform: translateX(0);
            opacity: 1;
          }
          to {
            transform: translateX(400px);
            opacity: 0;
          }
        }

        .toast.toast-info {
          background: #2563eb;
        }

        .toast.toast-success {
          background: #10b981;
        }

        .toast.toast-warning {
          background: #f59e0b;
        }

        .toast.toast-error {
          background: #ef4444;
        }

        .toast.hide {
          animation: slideOut 0.3s ease-out forwards;
        }

        @media (max-width: 600px) {
          .toast {
            bottom: 10px;
            right: 10px;
            left: 10px;
            max-width: none;
          }
        }
      `;
      document.head.appendChild(styleEl);
    }

    document.body.appendChild(toast);

    // Remover después del duración
    setTimeout(() => {
      toast.classList.add('hide');
      setTimeout(() => {
        document.body.removeChild(toast);
      }, 300);
    }, duration);
  };

  return {
    success: (message, duration) => showToast(message, 'success', duration || 3000),
    error: (message, duration) => showToast(message, 'error', duration || 4000),
    warning: (message, duration) => showToast(message, 'warning', duration || 3000),
    info: (message, duration) => showToast(message, 'info', duration || 3000)
  };
};
