import { createApp, h } from 'vue';
import ImageCropper from '../components/ImageCropper.vue';

const NON_IMAGE_PASSTHROUGH = true;

function isImage(file) {
  return file && typeof file === 'object' && typeof file.type === 'string' && file.type.startsWith('image/');
}

/**
 * Atveria apkarpymo modalą ir grąžina apdorotą File (arba null, jei vartotojas atšaukė).
 * Jeigu perduotas failas nėra paveikslėlis ir NON_IMAGE_PASSTHROUGH=true, grąžina originalą.
 */
export function cropImage(file, options = {}) {
  if (!file) return Promise.resolve(null);
  if (!isImage(file)) {
    return NON_IMAGE_PASSTHROUGH ? Promise.resolve(file) : Promise.resolve(null);
  }

  return new Promise((resolve) => {
    const host = document.createElement('div');
    document.body.appendChild(host);

    const cleanup = () => {
      try { app.unmount(); } catch (_) {}
      host.remove();
    };

    const app = createApp({
      render() {
        return h(ImageCropper, {
          file,
          aspectRatio: options.aspectRatio ?? 1,
          outputWidth: options.outputWidth ?? 1000,
          outputType: options.outputType ?? 'image/jpeg',
          quality: options.quality ?? 0.9,
          onSave: (cropped) => {
            cleanup();
            resolve(cropped || null);
          },
          onCancel: () => {
            cleanup();
            resolve(null);
          },
        });
      },
    });

    app.mount(host);
  });
}

/**
 * Apkarpo kelias nuotraukas iš eilės. Grąžina masyvą sėkmingai apdorotų failų
 * (atšauktos nuotraukos praleidžiamos).
 */
export async function cropImages(files, options = {}) {
  const list = Array.from(files || []);
  const out = [];
  for (const f of list) {
    const cropped = await cropImage(f, options);
    if (cropped) out.push(cropped);
  }
  return out;
}
