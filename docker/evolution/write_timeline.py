content = """// timeline.js
// Genera card PIXI per la timeline basate sui dati API

function generateTimelineCards(timelineData, modalUid) {
  const elements = [];
  const details = timelineData.details || [];

  if (!details || details.length === 0) {
    console.log('[Timeline] No timeline details found');
    return elements;
  }

  const cardWidth = 150;
  const cardHeight = 200;
  const cardSpacing = 50;
  const startX = 50;
  const startY = 50;
  const lineHeight = 2;
  const lineY = startY + cardHeight / 2;
  const pixelSize = 4;

  console.log(`[Timeline] Generating ${details.length} timeline cards`);

  const totalWidth = details.length * cardWidth + (details.length - 1) * cardSpacing;
  const lineElement = {
    uid: `timeline_line_${modalUid}`,
    type: 'rectangle',
    x: startX,
    y: lineY,
    width: totalWidth,
    height: lineHeight,
    color: 0xFF0000,
    attributes: { renderable: true, z_index: 20015 }
  };
  elements.push(lineElement);

  details.forEach((detail, index) => {
    const cardX = startX + index * (cardWidth + cardSpacing);
    const cardY = startY;

    const cardBorder = {
      uid: `timeline_card_border_${modalUid}_${index}`,
      type: 'rectangle',
      x: cardX - 3,
      y: cardY - 3,
      width: cardWidth + 6,
      height: cardHeight + 6,
      color: 0xCCCCCC,
      attributes: { renderable: true, z_index: 20020 }
    };
    elements.push(cardBorder);

    const cardBackground = {
      uid: `timeline_card_bg_${modalUid}_${index}`,
      type: 'rectangle',
      x: cardX,
      y: cardY,
      width: cardWidth,
      height: cardHeight,
      color: 0xE8E8E8,
      attributes: { renderable: true, z_index: 20021 }
    };
    elements.push(cardBackground);

    const imageHeight = 120;
    const imageX = cardX + 5;
    const imageY = cardY + 5;
    const imageWidth = cardWidth - 10;

    const imageBorder = {
      uid: `timeline_card_image_border_${modalUid}_${index}`,
      type: 'rectangle',
      x: imageX - 1,
      y: imageY - 1,
      width: imageWidth + 2,
      height: imageHeight + 2,
      color: 0x000000,
      attributes: { renderable: true, z_index: 20022 }
    };
    elements.push(imageBorder);

    const imagePlaceholder = {
      uid: `timeline_card_image_${modalUid}_${index}`,
      type: 'rectangle',
      x: imageX,
      y: imageY,
      width: imageWidth,
      height: imageHeight,
      color: 0xD0D0D0,
      attributes: { renderable: true, z_index: 20023 }
    };
    elements.push(imagePlaceholder);

    const imagePixels = detail.pixels || [];
    console.log(`[Timeline] Card ${index}: ${imagePixels.length} pixels to draw`);
    imagePixels.forEach((pixel, pIdx) => {
      const px = imageX + (pixel.x * pixelSize);
      const py = imageY + (pixel.y * pixelSize);
      if (px >= imageX && py >= imageY && px + pixelSize <= imageX + imageWidth && py + pixelSize <= imageY + imageHeight) {
        elements.push({
          uid: `timeline_card_pixel_${modalUid}_${index}_${pIdx}`,
          type: 'square',
          x: px,
          y: py,
          size: pixelSize,
          color: pixel.color || 0x000000,
          attributes: { renderable: true, z_index: 20030 }
        });
      }
    });

    const dateText = {
      uid: `timeline_card_date_${modalUid}_${index}`,
      type: 'text',
      x: cardX + 10,
      y: cardY + imageHeight + 15,
      text: `Creato: ${detail.creation_date || 'N/A'}`,
      color: 0x333333,
      fontSize: 11,
      fontFamily: 'Arial',
      attributes: { renderable: true, z_index: 20024 }
    };
    elements.push(dateText);

    const lifeText = {
      uid: `timeline_card_life_${modalUid}_${index}`,
      type: 'text',
      x: cardX + 10,
      y: cardY + imageHeight + 35,
      text: `Entity vivi: ${detail.population_life || 0}`,
      color: 0x008800,
      fontSize: 11,
      fontFamily: 'Arial',
      attributes: { renderable: true, z_index: 20024 }
    };
    elements.push(lifeText);

    const deathText = {
      uid: `timeline_card_death_${modalUid}_${index}`,
      type: 'text',
      x: cardX + 10,
      y: cardY + imageHeight + 55,
      text: `Entity morti: ${detail.population_death || 0}`,
      color: 0xAA0000,
      fontSize: 11,
      fontFamily: 'Arial',
      attributes: { renderable: true, z_index: 20024 }
    };
    elements.push(deathText);
  });

  console.log(`[Timeline] Generated ${elements.length} timeline elements`);
  return elements;
}

module.exports = {
  generateTimelineCards
};
"""

with open(r'c:\project\game\backend\docker\evolution\timeline.js', 'w') as f:
    f.write(content)
print('File written successfully')
"""

with open(r'c:\project\game\backend\docker\evolution\write_timeline.py', 'w') as f:
    f.write(content_py)
print('Script written')
PYEOF