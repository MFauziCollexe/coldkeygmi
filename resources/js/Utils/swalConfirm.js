import Swal from 'sweetalert2';

export async function swalConfirm(options = {}) {
  const {
    title = 'Konfirmasi',
    text = 'Apakah Anda yakin?',
    icon = 'warning',
    confirmButtonText = 'Ya',
    cancelButtonText = 'Batal',
    confirmButtonColor = '#4f46e5',
    cancelButtonColor = '#64748b',
  } = options;

  const result = await Swal.fire({
    title,
    text,
    icon,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText,
    confirmButtonColor,
    cancelButtonColor,
    reverseButtons: true,
    focusCancel: true,
  });

  return result.isConfirmed === true;
}

