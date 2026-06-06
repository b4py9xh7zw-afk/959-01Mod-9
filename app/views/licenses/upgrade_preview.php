<?php
$pageTitle = '套餐升级预览 - 许可证管理平台';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            套餐升级预览
        </h1>
        <a href="/licenses/view?id=<?php echo $license['id']; ?>" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
            ← 返回许可证详情
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">当前许可证信息</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-sm text-gray-500 mb-1">许可证密钥</div>
                <code class="text-sm font-mono text-gray-800"><?php echo htmlspecialchars($license['license_key']); ?></code>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-sm text-gray-500 mb-1">产品名称</div>
                <div class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($license['product_name']); ?></div>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-sm text-blue-600 mb-1">剩余期限</div>
                <div class="text-2xl font-bold text-blue-700"><?php echo $remainingDays; ?> 天</div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <h2 class="text-xl font-semibold text-gray-800">可升级套餐</h2>
        
        <?php if (empty($upgradePreviews)): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center">
                <p class="text-yellow-700">暂无可升级的套餐。</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-<?php echo min(count($upgradePreviews), 3); ?> gap-6">
                <?php foreach ($upgradePreviews as $preview): ?>
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden hover:shadow-xl transition-shadow">
                        <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-6 text-white">
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($preview['suite']['name']); ?></h3>
                            <p class="text-blue-100 text-sm mt-1"><?php echo htmlspecialchars($preview['suite']['description']); ?></p>
                            <div class="mt-4 flex items-baseline">
                                <span class="text-3xl font-bold">¥<?php echo number_format($preview['suite']['price'], 2); ?></span>
                                <span class="text-blue-200 ml-2">/<?php echo $preview['suite']['duration_days']; ?>天</span>
                            </div>
                        </div>
                        
                        <div class="p-6 space-y-4">
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 mb-2">包含产品</h4>
                                <div class="space-y-1">
                                    <?php foreach ($preview['suite_products'] as $sp): ?>
                                        <div class="flex items-center text-sm text-gray-700">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                            <?php echo htmlspecialchars($sp['name']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500 mb-2">插件变更</h4>
                                <?php if (!empty($preview['plugin_info']['added_plugins'])): ?>
                                    <div class="text-sm text-green-600 mb-1">
                                        <span class="font-medium">新增:</span>
                                        <?php echo implode(', ', array_column($preview['plugin_info']['added_plugins'], 'name')); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($preview['plugin_info']['removed_plugins'])): ?>
                                    <div class="text-sm text-orange-600">
                                        <span class="font-medium">移除:</span>
                                        <?php echo implode(', ', array_column($preview['plugin_info']['removed_plugins'], 'name')); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($preview['plugin_info']['added_plugins']) && empty($preview['plugin_info']['removed_plugins'])): ?>
                                    <div class="text-sm text-gray-500">插件无变化</div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="border-t border-gray-100 pt-4">
                                <h4 class="text-sm font-semibold text-gray-500 mb-2">费用明细</h4>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">原许可证剩余价值</span>
                                        <span class="text-gray-800">¥<?php echo number_format($preview['price_info']['remaining_value'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">新套餐价格</span>
                                        <span class="text-gray-800">¥<?php echo number_format($preview['price_info']['new_price'], 2); ?></span>
                                    </div>
                                    <div class="flex justify-between font-bold text-lg pt-2 border-t border-gray-100">
                                        <span class="text-blue-600">需补差价</span>
                                        <span class="text-blue-600">¥<?php echo number_format($preview['price_info']['price_difference'], 2); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <form method="POST" action="/licenses/upgrade/do" class="space-y-4">
                                <input type="hidden" name="license_id" value="<?php echo $license['id']; ?>">
                                <input type="hidden" name="new_product_id" value="<?php echo $preview['suite']['id']; ?>">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">旧授权处置方式</label>
                                    <select name="disposal_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <?php foreach ($disposalOptions as $value => $label): ?>
                                            <option value="<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">升级原因（可选）</label>
                                    <textarea name="reason" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="请输入升级原因..."></textarea>
                                </div>
                                
                                <button 
                                    type="submit"
                                    onclick="return confirm('确定要升级到 <?php echo htmlspecialchars($preview['suite']['name']); ?> 吗？升级后将创建新的许可证。');"
                                    class="w-full px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg font-semibold hover:from-blue-600 hover:to-purple-700 transition-all transform hover:scale-105"
                                >
                                    立即升级
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
        <h3 class="text-lg font-semibold text-blue-800 mb-3">升级须知</h3>
        <ul class="space-y-2 text-sm text-blue-700">
            <li class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                升级后将创建新的许可证，原许可证状态将变为"已升级"并保留所有历史记录
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                新许可证有效期从升级当日起计算，为期 <?php echo $preview['suite']['duration_days'] ?? 365; ?> 天
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                设备绑定记录将自动复制到新许可证，无需重新绑定
            </li>
            <li class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                升级后如需回退，可在新许可证详情页点击"回退套餐"按钮，所有历史记录将被保留
            </li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
