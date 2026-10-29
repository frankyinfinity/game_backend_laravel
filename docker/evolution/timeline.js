// timeline.js
// Genera elementi casuali PIXI per la timeline

function generateRandomElements() {
  const elements = [];
  const numElements = 20;

  for (let i = 0; i < numElements; i++) {
    const type = Math.floor(Math.random() * 4); // 0: square, 1: circle, 2: text, 3: rectangle
    const x = Math.random() * 800;
    const y = Math.random() * 600;
    const color = Math.floor(Math.random() * 0xFFFFFF);

    let element = {
      uid: `timeline_element_${Date.now()}_${i}`,
      type: '',
      x: x,
      y: y,
      color: color,
      attributes: { renderable: true, z_index: 20020 }
    };

    switch (type) {
      case 0: // square
        element.type = 'square';
        element.size = 30 + Math.random() * 50;
        break;
      case 1: // circle
        element.type = 'circle';
        element.radius = 15 + Math.random() * 35;
        break;
      case 2: // text
        element.type = 'text';
        const texts = ['Timeline', 'Evento', 'Step', 'Fase', 'Momento'];
        element.text = texts[Math.floor(Math.random() * texts.length)];
        element.fontSize = 14 + Math.random() * 20;
        element.fontFamily = 'Arial';
        break;
      case 3: // rectangle
        element.type = 'rectangle';
        element.width = 40 + Math.random() * 60;
        element.height = 30 + Math.random() * 50;
        break;
    }

    elements.push(element);
  }

  console.log(`[Timeline] Generated ${numElements} random elements`);
  return elements;
}

module.exports = {
  generateRandomElements
};
