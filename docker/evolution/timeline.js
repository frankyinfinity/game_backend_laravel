// timeline.js
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
  const lineHeight = 2; // Red line height
  const lineY = startY + cardHeight / 2; // Line in the middle of cards

  console.log(`[Timeline] Generating ${details.length} timeline cards`);

  // Draw horizontal red line first (behind cards)
  const totalWidth = details.length * cardWidth + (details.length - 1) * cardSpacing;
  const lineElement = {
    uid: `timeline_line_${modalUid}`,
    type: 'rectangle',
    x: startX,
    y: lineY,
    width: totalWidth,
    height: lineHeight,
    color: 0xFF0000, // Red
    attributes: { renderable: true, z_index: 20015 }
  };
  elements.push(lineElement);

  // Generate cards for each timeline detail
  details.forEach((detail, index) => {
    const cardX = startX + index * (cardWidth + cardSpacing);
    const cardY = startY;
    const borderRadius = 10; // Rounded corners

    // Card background (rectangle with rounded corners)
    const cardBackground = {
      uid: `timeline_card_bg_${modalUid}_${index}`,
      type: 'rounded_rectangle',
      x: cardX,
      y: cardY,
      width: cardWidth,
      height: cardHeight,
      color: 0xE8E8E8, // Gray background
      border_radius: borderRadius,
      attributes: { renderable: true, z_index: 20020 }
    };
    elements.push(cardBackground);

    // Card border (rounded rectangle outline) - gray
    const cardBorder = {
      uid: `timeline_card_border_${modalUid}_${index}`,
      type: 'rounded_rectangle',
      x: cardX,
      y: cardY,
      width: cardWidth,
      height: cardHeight,
      color: 0xCCCCCC, // Gray border
      border_radius: borderRadius,
      attributes: { renderable: true, z_index: 20021 }
    };
    elements.push(cardBorder);

    // Image (using sprite with URL)
    const imageHeight = 120;
    const imageX = cardX + 5;
    const imageY = cardY + 5;
    const imageWidth = cardWidth - 10;

    // Image border (black outline only, transparent background)
    const imageBorder = {
      uid: `timeline_card_image_border_${modalUid}_${index}`,
      type: 'rounded_rectangle_outline',
      x: imageX,
      y: imageY,
      width: imageWidth,
      height: imageHeight,
      color: 0x000000, // Black border
      border_radius: 5,
      border_width: 2,
      attributes: { renderable: true, z_index: 20022 }
    };
    elements.push(imageBorder);

    // Image placeholder (gray rectangle) - no sprite loading
    const imagePlaceholder = {
      uid: `timeline_card_image_${modalUid}_${index}`,
      type: 'rounded_rectangle',
      x: imageX + 2,
      y: imageY + 2,
      width: imageWidth - 4,
      height: imageHeight - 4,
      color: 0xD0D0D0, // Light gray
      border_radius: 5,
      attributes: { renderable: true, z_index: 20023 }
    };
    elements.push(imagePlaceholder);

    // Creation date text
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

    // Population life text (Entity vivi) - darker green
    const lifeText = {
      uid: `timeline_card_life_${modalUid}_${index}`,
      type: 'text',
      x: cardX + 10,
      y: cardY + imageHeight + 35,
      text: `Entity vivi: ${detail.population_life || 0}`,
      color: 0x008800, // Darker green
      fontSize: 11,
      fontFamily: 'Arial',
      attributes: { renderable: true, z_index: 20024 }
    };
    elements.push(lifeText);

    // Population death text (Entity morti)
    const deathText = {
      uid: `timeline_card_death_${modalUid}_${index}`,
      type: 'text',
      x: cardX + 10,
      y: cardY + imageHeight + 55,
      text: `Entity morti: ${detail.population_death || 0}`,
      color: 0xAA0000, // Red
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
